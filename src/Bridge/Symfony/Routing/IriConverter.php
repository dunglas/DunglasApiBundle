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

use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class IriConverter implements IriConverterInterface
{
    use ClassInfoTrait;

    const IRI_INTERNAL_PREFIX = '_api';

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $itemDataProvider;
    private $routeNameResolver;
    private $router;
    private $propertyAccessor;
    private $identifiersExtractor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ItemDataProviderInterface $itemDataProvider, RouteNameResolverInterface $routeNameResolver, RouterInterface $router, PropertyAccessorInterface $propertyAccessor = null, IdentifiersExtractorInterface $identifiersExtractor = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemDataProvider = $itemDataProvider;
        $this->routeNameResolver = $routeNameResolver;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();

        if (null === $identifiersExtractor) {
            @trigger_error('Not injecting ItemIdentifiersExtractor is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3', E_USER_DEPRECATED);
            $this->identifiersExtractor = new IdentifiersExtractor($this->propertyNameCollectionFactory, $this->propertyMetadataFactory, $this->propertyAccessor);
        } else {
            $this->identifiersExtractor = $identifiersExtractor;
        }
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

        if (!isset($parameters['_api_resource_class'], $parameters['id'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        if ($item = $this->itemDataProvider->getItem($parameters['_api_resource_class'], $parameters['id'], null, $context)) {
            return $item;
        }

        throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        $resourceClass = $this->getObjectClass($item);
        $routeName = $this->routeNameResolver->getRouteName($resourceClass, OperationType::ITEM);

        try {
            $identifiers = $this->generateIdentifiersUrl($this->identifiersExtractor->getIdentifiersFromItem($item));

            return $this->router->generate($routeName, ['id' => implode(';', $identifiers)], $referenceType);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException(sprintf(
                'Unable to generate an IRI for the item of type "%s"',
                $resourceClass
            ), $e->getCode(), $e);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf(
                'Unable to generate an IRI for the item of type "%s"',
                $resourceClass
            ), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        try {
            return $this->router->generate($this->routeNameResolver->getRouteName($resourceClass, OperationType::COLLECTION), [], $referenceType);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItemIriFromResourceClass(string $resourceClass, array $identifiers, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        try {
            return $this->router->generate($this->routeNameResolver->getRouteName($resourceClass, OperationType::ITEM), $identifiers, $referenceType);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContextIriFromShortName(string $shortName, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        return self::IRI_INTERNAL_PREFIX.$this->router->generate('api_jsonld_context', ['shortName' => $shortName], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiDocIri(int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        return self::IRI_INTERNAL_PREFIX.$this->router->generate('api_doc', [], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresourceIriFromResourceClass(string $resourceClass, array $context, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        try {
            return $this->router->generate($this->routeNameResolver->getRouteName($resourceClass, OperationType::SUBRESOURCE, $context), $context['subresource_identifiers'], $referenceType);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * Generate the identifier url.
     *
     * @param array $identifiers
     *
     * @return string[]
     */
    private function generateIdentifiersUrl(array $identifiers): array
    {
        if (1 === count($identifiers)) {
            return [rawurlencode((string) array_values($identifiers)[0])];
        }

        foreach ($identifiers as $name => $value) {
            $identifiers[$name] = sprintf('%s=%s', $name, $value);
        }

        return $identifiers;
    }
}
