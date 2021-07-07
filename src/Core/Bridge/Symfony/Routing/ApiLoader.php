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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\InvalidResourceException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiLoader extends Loader
{
    use ExpectDeprecationTrait;
    /**
     * @deprecated since version 2.1, to be removed in 3.0. Use {@see RouteNameGenerator::ROUTE_NAME_PREFIX} instead.
     */
    public const ROUTE_NAME_PREFIX = 'api_';
    public const DEFAULT_ACTION_PATTERN = 'api_platform.action.';

    private $fileLoader;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $operationPathResolver;
    private $container;
    private $formats;
    private $resourceClassDirectories;
    private $subresourceOperationFactory;
    private $graphqlEnabled;
    private $graphiQlEnabled;
    private $graphQlPlaygroundEnabled;
    private $entrypointEnabled;
    private $docsEnabled;
    private $identifiersExtractor;
    private $resourceCollectionMetadataFactory;

    public function __construct(KernelInterface $kernel, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, $resourceMetadataFactory, OperationPathResolverInterface $operationPathResolver, ContainerInterface $container, array $formats, array $resourceClassDirectories = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null, bool $graphqlEnabled = false, bool $entrypointEnabled = true, bool $docsEnabled = true, bool $graphiQlEnabled = false, bool $graphQlPlaygroundEnabled = false, IdentifiersExtractorInterface $identifiersExtractor = null)
    {
        /** @var string[]|string $paths */
        $paths = $kernel->locateResource('@ApiPlatformBundle/Resources/config/routing');
        $this->fileLoader = new XmlFileLoader(new FileLocator($paths));
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0, use %s instead.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class), \E_USER_DEPRECATED);
        }
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->container = $container;
        $this->formats = $formats;
        $this->resourceClassDirectories = $resourceClassDirectories;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->graphqlEnabled = $graphqlEnabled;
        $this->graphiQlEnabled = $graphiQlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->entrypointEnabled = $entrypointEnabled;
        $this->docsEnabled = $docsEnabled;
        $this->identifiersExtractor = $identifiersExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null): RouteCollection
    {
        $routeCollection = new RouteCollection();
        foreach ($this->resourceClassDirectories as $directory) {
            $routeCollection->addResource(new DirectoryResource($directory, '/\.php$/'));
        }

        $this->loadExternalFiles($routeCollection);
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                $this->loadLegacyMetadata($routeCollection, $resourceClass);
                $this->loadLegacySubresources($routeCollection, $resourceClass);
                continue;
            }

            foreach ($this->resourceMetadataFactory->create($resourceClass) as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if ($operation->getRouteName()) {
                        continue;
                    }

                    $route = new Route(
                        ($operation->getRoutePrefix() ?? '').$operation->getUriTemplate(),
                        [
                            '_controller' => $operation->getController(),
                            '_format' => null,
                            '_stateless' => $operation->getStateless(),
                            '_api_resource_class' => $resourceClass,
                            '_api_operation_name' => $operationName,
                        ] + ($operation->getDefaults() ?? []),
                        $operation->getRequirements() ?? [],
                        $operation->getOptions() ?? [],
                        $operation->getHost() ?? '',
                        $operation->getSchemes() ?? [],
                        [$operation->getMethod()],
                        $operation->getCondition() ?? ''
                    );

                    $routeCollection->add($operationName, $route);
                }
            }
        }

        return $routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_platform' === $type;
    }

    /**
     * Load external files.
     */
    private function loadExternalFiles(RouteCollection $routeCollection): void
    {
        if ($this->entrypointEnabled) {
            $routeCollection->addCollection($this->fileLoader->load('api.xml'));
        }

        if ($this->docsEnabled) {
            $routeCollection->addCollection($this->fileLoader->load('docs.xml'));
        }

        if ($this->graphqlEnabled) {
            $graphqlCollection = $this->fileLoader->load('graphql/graphql.xml');
            $graphqlCollection->addDefaults(['_graphql' => true]);
            $routeCollection->addCollection($graphqlCollection);
        }

        if ($this->graphiQlEnabled) {
            $graphiQlCollection = $this->fileLoader->load('graphql/graphiql.xml');
            $graphiQlCollection->addDefaults(['_graphql' => true]);
            $routeCollection->addCollection($graphiQlCollection);
        }

        if ($this->graphQlPlaygroundEnabled) {
            $graphQlPlaygroundCollection = $this->fileLoader->load('graphql/graphql_playground.xml');
            $graphQlPlaygroundCollection->addDefaults(['_graphql' => true]);
            $routeCollection->addCollection($graphQlPlaygroundCollection);
        }

        if (isset($this->formats['jsonld'])) {
            $routeCollection->addCollection($this->fileLoader->load('jsonld.xml'));
        }
    }

    /**
     * TODO: remove in 3.0.
     *
     * @group legacy
     */
    private function loadLegacyMetadata(RouteCollection $routeCollection, string $resourceClass)
    {
        $this->expectDeprecation(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0, use %s instead.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class));

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceShortName = $resourceMetadata->getShortName();

        if (null === $resourceShortName) {
            throw new InvalidResourceException(sprintf('Resource %s has no short name defined.', $resourceClass));
        }

        if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
            foreach ($collectionOperations as $operationName => $operation) {
                $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $resourceMetadata, OperationType::COLLECTION);
            }
        }

        if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
            foreach ($itemOperations as $operationName => $operation) {
                $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $resourceMetadata, OperationType::ITEM);
            }
        }
    }

    /**
     * TODO: remove in 3.0.
     *
     * @group legacy
     */
    private function loadLegacySubresources(RouteCollection $routeCollection, string $resourceClass)
    {
        $this->expectDeprecation(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0, use %s instead.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class));

        if (null === $this->subresourceOperationFactory) {
            return;
        }

        foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $operation) {
            if (null === $controller = $operation['controller'] ?? null) {
                $controller = self::DEFAULT_ACTION_PATTERN.'get_subresource';

                if (!$this->container->has($controller)) {
                    throw new RuntimeException(sprintf('There is no builtin action for the %s %s operation. You need to define the controller yourself.', OperationType::SUBRESOURCE, 'GET'));
                }
            }

            $routeCollection->add($operation['route_name'], new Route(
                $operation['path'],
                [
                    '_controller' => $controller,
                    '_format' => $operation['defaults']['_format'] ?? null,
                    '_stateless' => $operation['stateless'] ?? null,
                    '_api_resource_class' => $operation['resource_class'],
                    '_api_identifiers' => $operation['identifiers'],
                    '_api_has_composite_identifier' => false,
                    '_api_subresource_operation_name' => $operation['route_name'],
                    '_api_subresource_context' => [
                        'property' => $operation['property'],
                        'identifiers' => $operation['identifiers'],
                        'collection' => $operation['collection'],
                        'operationId' => $operationId,
                    ],
                ] + ($operation['defaults'] ?? []),
                $operation['requirements'] ?? [],
                $operation['options'] ?? [],
                $operation['host'] ?? '',
                $operation['schemes'] ?? [],
                ['GET'],
                $operation['condition'] ?? ''
            ));
        }
    }

    /**
     * Creates and adds a route for the given operation to the route collection.
     *
     * @throws RuntimeException
     */
    private function addRoute(RouteCollection $routeCollection, string $resourceClass, string $operationName, array $operation, ResourceMetadata $resourceMetadata, string $operationType): void
    {
        $resourceShortName = $resourceMetadata->getShortName();

        if (isset($operation['route_name'])) {
            if (!isset($operation['method'])) {
                @trigger_error(sprintf('Not setting the "method" attribute is deprecated and will not be supported anymore in API Platform 3.0, set it for the %s operation "%s" of the class "%s".', OperationType::COLLECTION === $operationType ? 'collection' : 'item', $operationName, $resourceClass), \E_USER_DEPRECATED);
            }

            return;
        }

        if (!isset($operation['method'])) {
            throw new RuntimeException(sprintf('Either a "route_name" or a "method" operation attribute must exist for the operation "%s" of the resource "%s".', $operationName, $resourceClass));
        }

        if (null === $controller = $operation['controller'] ?? null) {
            $controller = sprintf('%s%s_%s', self::DEFAULT_ACTION_PATTERN, strtolower($operation['method']), $operationType);

            if (!$this->container->has($controller)) {
                throw new RuntimeException(sprintf('There is no builtin action for the %s %s operation. You need to define the controller yourself.', $operationType, $operation['method']));
            }
        }

        if ($resourceMetadata->getItemOperations()) {
            $operation['identifiers'] = (array) ($operation['identifiers'] ?? $resourceMetadata->getAttribute('identifiers', $this->identifiersExtractor ? $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass) : ['id']));
        } else {
            $operation['identifiers'] = $operation['identifiers'] ?? [];
        }

        $operation['has_composite_identifier'] = \count($operation['identifiers']) > 1 ? $resourceMetadata->getAttribute('composite_identifier', true) : false;
        $path = trim(trim($resourceMetadata->getAttribute('route_prefix', '')), '/');
        $path .= $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);

        $route = new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => $operation['defaults']['_format'] ?? null,
                '_stateless' => $operation['stateless'],
                '_api_resource_class' => $resourceClass,
                '_api_identifiers' => $operation['identifiers'],
                '_api_has_composite_identifier' => $operation['has_composite_identifier'],
                sprintf('_api_%s_operation_name', $operationType) => $operationName,
            ] + ($operation['defaults'] ?? []),
            $operation['requirements'] ?? [],
            $operation['options'] ?? [],
            $operation['host'] ?? '',
            $operation['schemes'] ?? [],
            [$operation['method']],
            $operation['condition'] ?? ''
        );

        $routeCollection->add(RouteNameGenerator::generate($operationName, $resourceShortName, $operationType), $route);
    }
}
