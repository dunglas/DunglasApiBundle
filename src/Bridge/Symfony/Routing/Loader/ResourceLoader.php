<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use ApiPlatform\Core\Exception\InvalidResourceException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Tebaly <admin@freedomsex.net>
 */
final class ResourceLoader extends Loader
{
    public const DEFAULT_ACTION_PATTERN = 'api_platform.action.';

    private $resourceMetadataFactory;
    private $operationPathResolver;
    private $container;

    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        OperationPathResolverInterface $operationPathResolver,
        ContainerInterface $container
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resourceClass, $type = null): RouteCollection
    {
        $collection = new RouteCollection();
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceShortName = $resourceMetadata->getShortName();

        if (null === $resourceShortName) {
            throw new InvalidResourceException(sprintf('Resource %s has no short name defined.', $resourceClass));
        }
        if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
            foreach ($collectionOperations as $operationName => $operation) {
                $this->addRoute($collection, $resourceClass, $operationName, $operation, $resourceMetadata, OperationType::COLLECTION);
            }
        }
        if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
            foreach ($itemOperations as $operationName => $operation) {
                $this->addRoute($collection, $resourceClass, $operationName, $operation, $resourceMetadata, OperationType::ITEM);
            }
        }

        $subCollection = $this->import($resourceClass, 'api_subresource');
        $collection->addCollection($subCollection);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_resource' === $type;
    }

    /**
     * Creates and adds a route for the given operation to the route collection.
     *
     * @throws RuntimeException
     */
    private function addRoute(RouteCollection $collection, string $resourceClass, string $operationName, array $operation, ResourceMetadata $resourceMetadata, string $operationType): void
    {
        $resourceShortName = $resourceMetadata->getShortName();

        if (isset($operation['route_name'])) {
            if (!isset($operation['method'])) {
                @trigger_error(sprintf('Not setting the "method" attribute is deprecated and will not be supported anymore in API Platform 3.0, set it for the %s operation "%s" of the class "%s".', OperationType::COLLECTION === $operationType ? 'collection' : 'item', $operationName, $resourceClass), E_USER_DEPRECATED);
            }
            return;
        }

        if (!isset($operation['method'])) {
            throw new RuntimeException(sprintf('Either a "route_name" or a "method" operation attribute must exist for the operation "%s" of the resource "%s".', $operationName, $resourceClass));
        }

        $controller = $operation['controller'] ?? null;
        if (null === $controller) {
            $controller = sprintf(
                '%s%s_%s',
                self::DEFAULT_ACTION_PATTERN,
                strtolower($operation['method']),
                $operationType
            );
            if (!$this->container->has($controller)) {
                throw new RuntimeException(sprintf('There is no builtin action for the %s %s operation. You need to define the controller yourself.', $operationType, $operation['method']));
            }
        }

        $path = trim(
            trim($resourceMetadata->getAttribute('route_prefix', '')),
            '/'
        );
        $path .= $this->operationPathResolver->resolveOperationPath(
            $resourceShortName,
            $operation,
            $operationType,
            $operationName
        );

        $route = new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => null,
                '_api_resource_class' => $resourceClass,
                sprintf('_api_%s_operation_name', $operationType) => $operationName,
            ] + ($operation['defaults'] ?? []),
            $operation['requirements'] ?? [],
            $operation['options'] ?? [],
            $operation['host'] ?? '',
            $operation['schemes'] ?? [],
            [$operation['method']],
            $operation['condition'] ?? ''
        );

        $collection->add(
            RouteNameGenerator::generate(
                $operationName,
                $resourceShortName,
                $operationType),
            $route
        );
    }
}
