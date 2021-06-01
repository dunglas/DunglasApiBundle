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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\ContextAwareIdentifiersExtractorInterface;
use ApiPlatform\Core\Api\ContextAwareIriConverterInterface;
use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\OperationDataProviderTrait;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Util\AttributesExtractor;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use ApiPlatform\Metadata\Resource;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class IriConverter implements ContextAwareIriConverterInterface
{
    use OperationDataProviderTrait;
    use ResourceClassInfoTrait;

    private $routeNameResolver;
    private $router;
    private $identifiersExtractor;
    private $resourceCollectionMetadataFactory;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ItemDataProviderInterface $itemDataProvider, RouteNameResolverInterface $routeNameResolver, RouterInterface $router, PropertyAccessorInterface $propertyAccessor = null, IdentifiersExtractorInterface $identifiersExtractor = null, SubresourceDataProviderInterface $subresourceDataProvider = null, IdentifierConverterInterface $identifierConverter = null, ResourceClassResolverInterface $resourceClassResolver = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory = null)
    {
        $this->itemDataProvider = $itemDataProvider;
        $this->routeNameResolver = $routeNameResolver;
        $this->router = $router;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->identifierConverter = $identifierConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->identifiersExtractor = $identifiersExtractor ?: new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, $propertyAccessor ?? PropertyAccess::createPropertyAccessor());
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
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

        if (!isset($parameters['_api_resource_class'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        if (isset($parameters['_api_collection_operation_name'])) {
            throw new InvalidArgumentException(sprintf('The iri "%s" references a collection not an item.', $iri));
        }

        $attributes = AttributesExtractor::extractAttributes($parameters);

        try {
            $identifiers = $this->extractIdentifiers($parameters, $attributes);
        } catch (InvalidIdentifierException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->identifierConverter) {
            $context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] = true;
        }

        if (isset($attributes['subresource_operation_name'])) {
            if (($item = $this->getSubresourceData($identifiers, $attributes, $context)) && !\is_array($item)) {
                return $item;
            }

            throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
        }

        if ($item = $this->getItemData($identifiers, $attributes, $context)) {
            return $item;
        }

        throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, $referenceType = null): string
    {
        $resourceClass = $this->getResourceClass($item, true);
        // bc layer
        $context = [];
        if (\is_array($referenceType)) {
            $context = $referenceType;
            $referenceType = $context['reference_type'] ?? null;
        } elseif (\is_int($referenceType)) {
            @trigger_error('Using getIriFromItem with a referenceType is deprecated, use an array for example: ["reference_type" => UrlGeneratorInterface::ABS_PATH]', \E_USER_DEPRECATED);
        }

        // Special case where the Resource has no identifiers (it's a collection) and we want the operation tight to the item
        if (!($context['identifiers'] ?? true) && $this->resourceCollectionMetadataFactory) {
            $collection = $this->resourceCollectionMetadataFactory->create($resourceClass);
            foreach ($collection as $resource) {
                foreach ($resource->operations as $key => $operation) {
                    if ('GET' === $operation->method && $operation->identifiers) {
                        $context['operation_name'] = $key;
                        $context['identifiers'] = $operation->identifiers;
                        break 2;
                    }
                }
            }
        }

        try {
            $identifiers = $this->identifiersExtractor instanceof ContextAwareIdentifiersExtractorInterface ? $this->identifiersExtractor->getIdentifiersFromItem($item, $context) : $this->identifiersExtractor->getIdentifiersFromItem($item);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass), $e->getCode(), $e);
        }

        if (isset($context['operation_name'])) {
            return $this->router->generate($context['operation_name'], $identifiers, $this->getReferenceType($resourceClass, $referenceType));
        }

        return $this->getItemIriFromResourceClass($resourceClass, $identifiers, $this->getReferenceType($resourceClass, $referenceType));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = null): string
    {
        // TODO: use ResourceMetadataCollection somehow to fetch the correct route, this makes no sense as relying a lot on OperationType
        try {
            return $this->router->generate($this->getRouteName($resourceClass, OperationType::COLLECTION), [], $this->getReferenceType($resourceClass, $referenceType));
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItemIriFromResourceClass(string $resourceClass, array $identifiers, int $referenceType = null): string
    {
        // TODO: use ResourceMetadataCollection somehow to fetch the correct route, this makes no sense as relying a lot on OperationType
        $routeName = $this->getRouteName($resourceClass, OperationType::ITEM);
        $metadata = $this->resourceMetadataFactory->create($resourceClass);

        if (\count($identifiers) > 1 && true === $metadata->getAttribute('composite_identifier', true)) {
            $identifiers = ['id' => CompositeIdentifierParser::stringify($identifiers)];
        }

        try {
            return $this->router->generate($routeName, $identifiers, $this->getReferenceType($resourceClass, $referenceType));
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresourceIriFromResourceClass(string $resourceClass, array $context, int $referenceType = null): string
    {
        @trigger_error('getSubresourceIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0', \E_USER_DEPRECATED);

        try {
            return $this->router->generate($this->routeNameResolver->getRouteName($resourceClass, OperationType::SUBRESOURCE, $context), $context['subresource_identifiers'], $this->getReferenceType($resourceClass, $referenceType));
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    private function getReferenceType(string $resourceClass, ?int $referenceType): ?int
    {
        if (null === $referenceType && null !== $this->resourceMetadataFactory) {
            $metadata = $this->resourceMetadataFactory->create($resourceClass);
            $referenceType = $metadata->getAttribute('url_generation_strategy');
        }

        return $referenceType ?? UrlGeneratorInterface::ABS_PATH;
    }

    private function getRouteName(string $resourceClass, string $operationType)
    {
        if (null !== $this->resourceCollectionMetadataFactory) {
            try {
                $collection = $this->resourceCollectionMetadataFactory->create($resourceClass);

                foreach ($collection as $resource) {
                    foreach ($resource->operations as $key => $operation) {
                        // TODO: This is wrong as it can happen but as we need to keep a layer with declaring every operation on a single entity we need to keep the behavior
                        if (OperationType::COLLECTION === $operationType && $operation->identifiers) {
                            continue;
                        }

                        if ('GET' === $operation->method) {
                            return $key;
                        }
                    }
                }
            } catch (ResourceClassNotFoundException $e) {
                // This is probably a pre-2.7 resource
            }
        }

        return $this->routeNameResolver->getRouteName($resourceClass, $operationType);
    }
}
