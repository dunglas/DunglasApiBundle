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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves the HTTP method associated with an operation, extended for Symfony routing.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @deprecated since API Platform 2.5, use the "method" attribute instead
 */
final class OperationMethodResolver implements OperationMethodResolverInterface
{
    private $router;
    private $resourceMetadataFactory;

    public function __construct(RouterInterface $router, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        @trigger_error(sprintf('The "%s" class is deprecated since API Platform 2.5, use the "method" attribute instead.', __CLASS__), \E_USER_DEPRECATED);

        $this->router = $router;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionOperationMethod(string $resourceClass, string $operationName): string
    {
        return $this->getOperationMethod($resourceClass, $operationName, OperationType::COLLECTION);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperationMethod(string $resourceClass, string $operationName): string
    {
        return $this->getOperationMethod($resourceClass, $operationName, OperationType::ITEM);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionOperationRoute(string $resourceClass, string $operationName): Route
    {
        return $this->getOperationRoute($resourceClass, $operationName, OperationType::COLLECTION);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperationRoute(string $resourceClass, string $operationName): Route
    {
        return $this->getOperationRoute($resourceClass, $operationName, OperationType::ITEM);
    }

    /**
     * @throws RuntimeException
     */
    private function getOperationMethod(string $resourceClass, string $operationName, string $operationType): string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (OperationType::ITEM === $operationType) {
            $method = $resourceMetadata->getItemOperationAttribute($operationName, 'method');
        } else {
            $method = $resourceMetadata->getCollectionOperationAttribute($operationName, 'method');
        }

        if (null !== $method) {
            return strtoupper($method);
        }

        if (null === $routeName = $this->getRouteName($resourceMetadata, $operationName, $operationType)) {
            throw new RuntimeException(sprintf('Either a "route_name" or a "method" operation attribute must exist for the operation "%s" of the resource "%s".', $operationName, $resourceClass));
        }

        return $this->getRoute($routeName)->getMethods()[0] ?? 'GET';
    }

    /**
     * Gets the route related to the given operation.
     *
     * @throws RuntimeException
     */
    private function getOperationRoute(string $resourceClass, string $operationName, string $operationType): Route
    {
        $routeName = $this->getRouteName($this->resourceMetadataFactory->create($resourceClass), $operationName, $operationType);
        if (null !== $routeName) {
            return $this->getRoute($routeName);
        }

        $operationNameKey = sprintf('_api_%s_operation_name', $operationType);

        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            $currentResourceClass = $route->getDefault('_api_resource_class');
            $currentOperationName = $route->getDefault($operationNameKey);

            if ($resourceClass === $currentResourceClass && $operationName === $currentOperationName) {
                return $route;
            }
        }

        throw new RuntimeException(sprintf('No route found for operation "%s" for type "%s".', $operationName, $resourceClass));
    }

    /**
     * Gets the route name or null if not defined.
     */
    private function getRouteName(ResourceMetadata $resourceMetadata, string $operationName, string $operationType): ?string
    {
        if (OperationType::ITEM === $operationType) {
            return $resourceMetadata->getItemOperationAttribute($operationName, 'route_name');
        }

        return $resourceMetadata->getCollectionOperationAttribute($operationName, 'route_name');
    }

    /**
     * Gets the route with the given name.
     *
     * @throws RuntimeException
     */
    private function getRoute(string $routeName): Route
    {
        foreach ($this->router->getRouteCollection() as $name => $route) {
            if ($routeName === $name) {
                return $route;
            }
        }

        throw new RuntimeException(sprintf('The route "%s" does not exist.', $routeName));
    }
}
