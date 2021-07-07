<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Bridge\Symfony\Routing;

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Identifier\ContextAwareIdentifierConverterInterface;
use ApiPlatform\Core\Util\AttributesExtractor;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @experimental
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IriConverter implements IriConverterInterface
{
    use ResourceClassInfoTrait;

    private $stateProvider;
    private $router;
    private $identifiersExtractor;
    private $identifierConverter;
    private $resourceMetadataCollectionFactory;

    public function __construct(ProviderInterface $stateProvider, RouterInterface $router, IdentifiersExtractorInterface $identifiersExtractor, ContextAwareIdentifierConverterInterface $identifierConverter, ResourceClassResolverInterface $resourceClassResolver, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
        $this->stateProvider = $stateProvider;
        $this->router = $router;
        $this->identifierConverter = $identifierConverter;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        // For the ResourceClassInfoTrait
        $this->resourceClassResolver = $resourceClassResolver;
        $this->resourceMetadataFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri(string $iri, array $context = [])
    {
        try {
            $parameters = $this->router->match($iri);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('No route matches "%s".', $iri), $e->getCode(), $e);
        }

        if (!isset($parameters['_api_resource_class'], $parameters['_api_operation_name'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        $attributes = AttributesExtractor::extractAttributes($parameters);
        $operation = $this->resourceMetadataFactory->create($attributes['resource_class'])->getOperation($attributes['operation_name']);
        $shouldParseCompositeIdentifiers = $operation->getCompositeIdentifier() && \count($operation->getIdentifiers()) > 1;

        foreach ($operation->getIdentifiers() as $parameterName => $identifiedBy) {
            if (!isset($parameters[$parameterName])) {
                if (!isset($parameters['id'])) {
                    throw new InvalidIdentifierException(sprintf('Parameter "%s" not found, check the identifiers configuration.', $parameterName));
                }

                $parameterName = 'id';
            }

            if ($shouldParseCompositeIdentifiers) {
                $identifiers = CompositeIdentifierParser::parse($parameters[$parameterName]);
                break;
            }

            $identifiers[$parameterName] = $parameters[$parameterName];
        }

        try {
            $identifiers = $this->identifierConverter->convert($identifiers, $attributes['resource_class'], ['identifiers' => $operation->getIdentifiers()]);
        } catch (InvalidIdentifierException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if ($item = $this->stateProvider->provide($attributes['resource_class'], $identifiers, $attributes['operation_name'], ['operation' => $operation])) {
            return $item;
        }

        throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, string $operationName = null, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string
    {
        $resourceClass = $this->getResourceClass($item, true);

        $operation = $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName);

        try {
            $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($item, $operationName, ['operation' => $operation]);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass), $e->getCode(), $e);
        }

        if (\count($identifiers) > 1 && $operation->getCompositeIdentifier()) {
            $identifiers = [key($operation->getIdentifiers()) => CompositeIdentifierParser::stringify($identifiers)];
        }

        try {
            return $this->router->generate($operation->getName(), $identifiers, $referenceType ?? $operation->getUrlGenerationStrategy());
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResourceClass(string $resourceClass, string $operationName = null, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string
    {
        // TODO: 3.0 remove the condition
        if ($context['extra_properties']['is_legacy_subresource'] ?? false) {
            trigger_deprecation('api-platform/core', '2.7', 'The IRI will change and match the first operation of the resource. Switch to an alternate resource when possible instead of using subresources.');
            $operation = $this->resourceMetadataFactory->create($resourceClass)->getOperation($context['extra_properties']['legacy_subresource_operation_name']);
        } else {
            $operation = $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName);
        }

        try {
            return $this->router->generate($operation->getName(), [], $referenceType ?? $operation->getUrlGenerationStrategy());
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }
}
