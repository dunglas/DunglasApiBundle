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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\AbstractFilter as DoctrineMongoDbOdmAbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface as DoctrineQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter as DoctrineOrmAbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface;
use ApiPlatform\Core\Bridge\Symfony\Validator\ValidationGroupsGeneratorInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInitializerInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface as GraphQlTypeInterface;
use Doctrine\Common\Annotations\Annotation;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * The extension of this bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiPlatformExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['framework'])) {
            $container->prependExtensionConfig('framework', [
                'serializer' => [
                    'enabled' => true,
                ],
            ]);
            $container->prependExtensionConfig('framework', [
                'property_info' => [
                    'enabled' => true,
                ],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $formats = $this->getFormats($config['formats']);
        $patchFormats = $this->getFormats($config['patch_formats']);
        $errorFormats = $this->getFormats($config['error_formats']);

        // Backward Compatibility layer
        if (isset($formats['jsonapi']) && !isset($patchFormats['jsonapi'])) {
            $patchFormats['jsonapi'] = ['application/vnd.api+json'];
        }

        $this->registerCommonConfiguration($container, $config, $loader, $formats, $patchFormats, $errorFormats);

        $this->registerRouterLoading($container, $config);
        $this->registerMetadataConfiguration($container, $config, $loader);
        $this->registerOAuthConfiguration($container, $config);
        $this->registerSwaggerConfiguration($container, $config, $loader);
        $this->registerJsonApiConfiguration($formats, $loader);
        $this->registerJsonLdHydraConfiguration($container, $formats, $loader, $config['enable_docs']);
        $this->registerJsonHalConfiguration($formats, $loader);
        $this->registerJsonProblemConfiguration($errorFormats, $loader);
        $this->registerGraphQlConfiguration($container, $config, $loader);
        $this->registerLegacyBundlesConfiguration($container, $config, $loader);
        $this->registerCacheConfiguration($container);
        $this->registerDoctrineOrmConfiguration($container, $config, $loader);
        $this->registerDoctrineMongoDbOdmConfiguration($container, $config, $loader);
        $this->registerHttpCacheConfiguration($container, $config, $loader);
        $this->registerValidatorConfiguration($container, $config, $loader);
        $this->registerDataCollectorConfiguration($container, $config, $loader);
        $this->registerMercureConfiguration($container, $config, $loader);
        $this->registerMessengerConfiguration($container, $config, $loader);
        $this->registerElasticsearchConfiguration($container, $config, $loader);
        $this->registerDataTransformerConfiguration($container);
        $this->registerSecurityConfiguration($container, $loader);

        $container->registerForAutoconfiguration(DataPersisterInterface::class)
            ->addTag('api_platform.data_persister');
        $container->registerForAutoconfiguration(ItemDataProviderInterface::class)
            ->addTag('api_platform.item_data_provider');
        $container->registerForAutoconfiguration(CollectionDataProviderInterface::class)
            ->addTag('api_platform.collection_data_provider');
        $container->registerForAutoconfiguration(SubresourceDataProviderInterface::class)
            ->addTag('api_platform.subresource_data_provider');
        $container->registerForAutoconfiguration(FilterInterface::class)
            ->addTag('api_platform.filter');

        if ($container->hasParameter('test.client.parameters')) {
            $loader->load('test.xml');

            if (!class_exists(AbstractBrowser::class) || !trait_exists(HttpClientTrait::class)) {
                $container->removeDefinition('test.api_platform.client');
            }
        }
    }

    private function registerCommonConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader, array $formats, array $patchFormats, array $errorFormats): void
    {
        $loader->load('api.xml');
        $loader->load('data_persister.xml');
        $loader->load('data_provider.xml');
        $loader->load('filter.xml');

        $container->getDefinition('api_platform.operation_method_resolver')
            ->setDeprecated(...$this->buildDeprecationArgs('2.5', 'The "%service_id%" service is deprecated since API Platform 2.5.'));
        $container->getDefinition('api_platform.formats_provider')
            ->setDeprecated(...$this->buildDeprecationArgs('2.5', 'The "%service_id%" service is deprecated since API Platform 2.5.'));
        $container->getAlias('ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface')
            ->setDeprecated(...$this->buildDeprecationArgs('2.5', 'The "%alias_id%" alias is deprecated since API Platform 2.5.'));
        $container->getDefinition('api_platform.operation_path_resolver.underscore')
            ->setDeprecated(...$this->buildDeprecationArgs('2.1', 'The "%service_id%" service is deprecated since API Platform 2.1 and will be removed in 3.0. Use "api_platform.path_segment_name_generator.underscore" instead.'));
        $container->getDefinition('api_platform.operation_path_resolver.dash')
            ->setDeprecated(...$this->buildDeprecationArgs('2.1', 'The "%service_id%" service is deprecated since API Platform 2.1 and will be removed in 3.0. Use "api_platform.path_segment_name_generator.dash" instead.'));
        $container->getDefinition('api_platform.filters')
            ->setDeprecated(...$this->buildDeprecationArgs('2.1', 'The "%service_id%" service is deprecated since 2.1 and will be removed in 3.0. Use the "api_platform.filter_locator" service instead.'));

        if (class_exists(Uuid::class)) {
            $loader->load('ramsey_uuid.xml');
        }

        if (class_exists(AbstractUid::class)) {
            $loader->load('symfony_uid.xml');
        }

        $container->setParameter('api_platform.enable_entrypoint', $config['enable_entrypoint']);
        $container->setParameter('api_platform.enable_docs', $config['enable_docs']);
        $container->setParameter('api_platform.title', $config['title']);
        $container->setParameter('api_platform.description', $config['description']);
        $container->setParameter('api_platform.version', $config['version']);
        $container->setParameter('api_platform.show_webby', $config['show_webby']);
        $container->setParameter('api_platform.url_generation_strategy', $config['defaults']['url_generation_strategy'] ?? UrlGeneratorInterface::ABS_PATH);
        $container->setParameter('api_platform.exception_to_status', $config['exception_to_status']);
        $container->setParameter('api_platform.formats', $formats);
        $container->setParameter('api_platform.patch_formats', $patchFormats);
        $container->setParameter('api_platform.error_formats', $errorFormats);
        $container->setParameter('api_platform.allow_plain_identifiers', $config['allow_plain_identifiers']);
        $container->setParameter('api_platform.eager_loading.enabled', $this->isConfigEnabled($container, $config['eager_loading']));
        $container->setParameter('api_platform.eager_loading.max_joins', $config['eager_loading']['max_joins']);
        $container->setParameter('api_platform.eager_loading.fetch_partial', $config['eager_loading']['fetch_partial']);
        $container->setParameter('api_platform.eager_loading.force_eager', $config['eager_loading']['force_eager']);
        $container->setParameter('api_platform.collection.exists_parameter_name', $config['collection']['exists_parameter_name']);
        $container->setParameter('api_platform.collection.order', $config['defaults']['order'] ?? $config['collection']['order']);
        $container->setParameter('api_platform.collection.order_parameter_name', $config['collection']['order_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled', $this->isConfigEnabled($container, $config['defaults']['pagination_enabled'] ?? $config['collection']['pagination']));
        $container->setParameter('api_platform.collection.pagination.partial', $config['defaults']['pagination_partial'] ?? $config['collection']['pagination']['partial']);
        $container->setParameter('api_platform.collection.pagination.client_enabled', $config['defaults']['pagination_client_enabled'] ?? $config['collection']['pagination']['client_enabled']);
        $container->setParameter('api_platform.collection.pagination.client_items_per_page', $config['defaults']['pagination_client_items_per_page'] ?? $config['collection']['pagination']['client_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.client_partial', $config['defaults']['pagination_client_partial'] ?? $config['collection']['pagination']['client_partial']);
        $container->setParameter('api_platform.collection.pagination.items_per_page', $config['defaults']['pagination_items_per_page'] ?? $config['collection']['pagination']['items_per_page']);
        $container->setParameter('api_platform.collection.pagination.maximum_items_per_page', $config['defaults']['pagination_maximum_items_per_page'] ?? $config['collection']['pagination']['maximum_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.page_parameter_name', $config['defaults']['pagination_page_parameter_name'] ?? $config['collection']['pagination']['page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled_parameter_name', $config['defaults']['pagination_enabled_parameter_name'] ?? $config['collection']['pagination']['enabled_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.items_per_page_parameter_name', $config['defaults']['pagination_items_per_page_parameter_name'] ?? $config['collection']['pagination']['items_per_page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.partial_parameter_name', $config['defaults']['pagination_partial_parameter_name'] ?? $config['collection']['pagination']['partial_parameter_name']);
        $container->setParameter('api_platform.collection.pagination', $this->getPaginationDefaults($config['defaults'] ?? [], $config['collection']['pagination']));
        $container->setParameter('api_platform.http_cache.etag', $config['defaults']['cache_headers']['etag'] ?? $config['http_cache']['etag']);
        $container->setParameter('api_platform.http_cache.max_age', $config['defaults']['cache_headers']['max_age'] ?? $config['http_cache']['max_age']);
        $container->setParameter('api_platform.http_cache.shared_max_age', $config['defaults']['cache_headers']['shared_max_age'] ?? $config['http_cache']['shared_max_age']);
        $container->setParameter('api_platform.http_cache.vary', $config['defaults']['cache_headers']['vary'] ?? $config['http_cache']['vary']);
        $container->setParameter('api_platform.http_cache.public', $config['defaults']['cache_headers']['public'] ?? $config['http_cache']['public']);
        $container->setParameter('api_platform.http_cache.invalidation.max_header_length', $config['defaults']['cache_headers']['invalidation']['max_header_length'] ?? $config['http_cache']['invalidation']['max_header_length']);

        $container->setAlias('api_platform.operation_path_resolver.default', $config['default_operation_path_resolver']);
        $container->setAlias('api_platform.path_segment_name_generator', $config['path_segment_name_generator']);

        if ($config['name_converter']) {
            $container->setAlias('api_platform.name_converter', $config['name_converter']);
        }
        $container->setParameter('api_platform.defaults', $this->normalizeDefaults($config['defaults'] ?? []));
    }

    private function registerRouterLoading(ContainerBuilder $container, array $config)
    {
        $apiLoader = $container->getDefinition('api_platform.route_loader');
        $apiLoader->setArguments([
            $config['routing']['autoload']['externals'],
            $config['routing']['autoload']['directories'],
            $config['routing']['autoload']['resources'],
        ]);
    }

    /**
     * This method will be removed in 3.0 when "defaults" will be the regular configuration path for the pagination.
     */
    private function getPaginationDefaults(array $defaults, array $collectionPaginationConfiguration): array
    {
        $paginationOptions = [];

        foreach ($defaults as $key => $value) {
            if (0 !== strpos($key, 'pagination_')) {
                continue;
            }

            $paginationOptions[str_replace('pagination_', '', $key)] = $value;
        }

        return array_merge($collectionPaginationConfiguration, $paginationOptions);
    }

    private function normalizeDefaults(array $defaults): array
    {
        $normalizedDefaults = ['attributes' => []];
        $rootLevelOptions = [
            'description',
            'iri',
            'item_operations',
            'collection_operations',
            'graphql',
        ];

        foreach ($defaults as $option => $value) {
            if (\in_array($option, $rootLevelOptions, true)) {
                $normalizedDefaults[$option] = $value;
            } else {
                $normalizedDefaults['attributes'][$option] = $value;
            }
        }

        return $normalizedDefaults;
    }

    private function registerMetadataConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $loader->load('metadata/metadata.xml');
        $loader->load('metadata/xml.xml');

        [$xmlResources, $yamlResources] = $this->getResourcesToWatch($container, $config);

        if (!empty($config['resource_class_directories'])) {
            $container->setParameter('api_platform.resource_class_directories', array_merge(
                $config['resource_class_directories'], $container->getParameter('api_platform.resource_class_directories')
            ));
        }

        $container->getDefinition('api_platform.metadata.extractor.xml')->replaceArgument(0, $xmlResources);

        if (class_exists(Annotation::class)) {
            $loader->load('metadata/annotation.xml');
        }

        if (class_exists(Yaml::class)) {
            $loader->load('metadata/yaml.xml');
            $container->getDefinition('api_platform.metadata.extractor.yaml')->replaceArgument(0, $yamlResources);
        }

        if (interface_exists(DocBlockFactoryInterface::class)) {
            $loader->load('metadata/php_doc.xml');
        }
    }

    private function getBundlesResourcesPaths(ContainerBuilder $container, array $config): array
    {
        $result = [];
        $bundles = $container->getParameter('kernel.bundles_metadata');
        foreach ($bundles as $bundle) {
            $dirname = $bundle['path'];
            if ($config['resources']['autoload']) {
                if (in_array($bundle['namespace'], $config['resources']['bundles']['exclude'])) {
                    continue;
                }
            } else {
                if (!in_array($bundle['namespace'], $config['resources']['bundles']['include'])) {
                    continue;
                }
            }
            $result = $this->setResourcesPath($dirname, $container, $config);
        }
        return $result;
    }

    private function setResourcesPath($dirname, ContainerBuilder $container, array $config): array
    {
        $paths = [];
        $result = [];
        if ($config['resources']['sources']['configs']) {
            foreach (['.yaml', '.yml', '.xml', ''] as $extension) {
                $paths[] = "$dirname/Resources/config/api_resources$extension";
            }
        }
        if ($config['resources']['sources']['doctrine']) {
            $paths[] = "$dirname/Entity";
        }
        if ($config['resources']['sources']['mongodb']) {
            $paths[] = "$dirname/Document";
        }
        foreach ($paths as $path) {
            if ($container->fileExists($path, false)) {
                $result[] = $path;
            }
        }
        return $result;
    }


    private function getResourcesToWatch(ContainerBuilder $container, array $config): array
    {
        $paths = array_unique(array_merge($config['mapping']['paths'], $this->getBundlesResourcesPaths($container, $config)));

        // Flex structure (only if nothing specified)
        $projectDir = $container->getParameter('kernel.project_dir');
        if (!$paths && is_dir($dir = "$projectDir/config/api_platform")) {
            $paths = [$dir];
        }

        $resources = ['yml' => [], 'xml' => [], 'dir' => []];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (Finder::create()->followLinks()->files()->in($path)->name('/\.(xml|ya?ml)$/') as $file) {
                    $resources['yaml' === ($extension = $file->getExtension()) ? 'yml' : $extension][] = $file->getRealPath();
                }

                $resources['dir'][] = $path;
                $container->addResource(new DirectoryResource($path, '/\.(xml|ya?ml|php)$/'));

                continue;
            }

            if ($container->fileExists($path, false)) {
                if (!preg_match('/\.(xml|ya?ml)$/', $path, $matches)) {
                    throw new RuntimeException(sprintf('Unsupported mapping type in "%s", supported types are XML & YAML.', $path));
                }

                $resources['yaml' === $matches[1] ? 'yml' : $matches[1]][] = $path;

                continue;
            }

            throw new RuntimeException(sprintf('Could not open file or directory "%s".', $path));
        }

        $container->setParameter('api_platform.resource_class_directories', $resources['dir']);

        return [$resources['xml'], $resources['yml']];
    }

    private function registerOAuthConfiguration(ContainerBuilder $container, array $config): void
    {
        if (!$config['oauth']) {
            return;
        }

        $container->setParameter('api_platform.oauth.enabled', $this->isConfigEnabled($container, $config['oauth']));
        $container->setParameter('api_platform.oauth.clientId', $config['oauth']['clientId']);
        $container->setParameter('api_platform.oauth.clientSecret', $config['oauth']['clientSecret']);
        $container->setParameter('api_platform.oauth.type', $config['oauth']['type']);
        $container->setParameter('api_platform.oauth.flow', $config['oauth']['flow']);
        $container->setParameter('api_platform.oauth.tokenUrl', $config['oauth']['tokenUrl']);
        $container->setParameter('api_platform.oauth.authorizationUrl', $config['oauth']['authorizationUrl']);
        $container->setParameter('api_platform.oauth.refreshUrl', $config['oauth']['refreshUrl']);
        $container->setParameter('api_platform.oauth.scopes', $config['oauth']['scopes']);
    }

    /**
     * Registers the Swagger, ReDoc and Swagger UI configuration.
     */
    private function registerSwaggerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $container->setParameter('api_platform.swagger.versions', $config['swagger']['versions']);

        if (empty($config['swagger']['versions'])) {
            return;
        }

        $loader->load('json_schema.xml');
        $loader->load('swagger.xml');
        $loader->load('openapi.xml');
        $loader->load('swagger-ui.xml');

        if (!$config['enable_swagger_ui'] && !$config['enable_re_doc']) {
            // Remove the listener but keep the controller to allow customizing the path of the UI
            $container->removeDefinition('api_platform.swagger.listener.ui');
        }

        $container->setParameter('api_platform.enable_swagger_ui', $config['enable_swagger_ui']);
        $container->setParameter('api_platform.enable_re_doc', $config['enable_re_doc']);
        $container->setParameter('api_platform.swagger.api_keys', $config['swagger']['api_keys']);
    }

    private function registerJsonApiConfiguration(array $formats, XmlFileLoader $loader): void
    {
        if (!isset($formats['jsonapi'])) {
            return;
        }

        $loader->load('jsonapi.xml');
    }

    private function registerJsonLdHydraConfiguration(ContainerBuilder $container, array $formats, XmlFileLoader $loader, bool $docEnabled): void
    {
        if (!isset($formats['jsonld'])) {
            return;
        }

        $loader->load('jsonld.xml');
        $loader->load('hydra.xml');

        if (!$container->has('api_platform.json_schema.schema_factory')) {
            $container->removeDefinition('api_platform.hydra.json_schema.schema_factory');
        }

        if (!$docEnabled) {
            $container->removeDefinition('api_platform.hydra.listener.response.add_link_header');
        }
    }

    private function registerJsonHalConfiguration(array $formats, XmlFileLoader $loader): void
    {
        if (!isset($formats['jsonhal'])) {
            return;
        }

        $loader->load('hal.xml');
    }

    private function registerJsonProblemConfiguration(array $errorFormats, XmlFileLoader $loader): void
    {
        if (!isset($errorFormats['jsonproblem'])) {
            return;
        }

        $loader->load('problem.xml');
    }

    private function registerGraphQlConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $enabled = $this->isConfigEnabled($container, $config['graphql']);

        $container->setParameter('api_platform.graphql.enabled', $enabled);
        $container->setParameter('api_platform.graphql.graphiql.enabled', $enabled && $this->isConfigEnabled($container, $config['graphql']['graphiql']));
        $container->setParameter('api_platform.graphql.graphql_playground.enabled', $enabled && $this->isConfigEnabled($container, $config['graphql']['graphql_playground']));
        $container->setParameter('api_platform.graphql.collection.pagination', $config['graphql']['collection']['pagination']);

        if (!$enabled) {
            return;
        }

        $container->setParameter('api_platform.graphql.default_ide', $config['graphql']['default_ide']);
        $container->setParameter('api_platform.graphql.nesting_separator', $config['graphql']['nesting_separator']);

        $loader->load('graphql.xml');

        $container->registerForAutoconfiguration(QueryItemResolverInterface::class)
            ->addTag('api_platform.graphql.query_resolver');
        $container->registerForAutoconfiguration(QueryCollectionResolverInterface::class)
            ->addTag('api_platform.graphql.query_resolver');
        $container->registerForAutoconfiguration(MutationResolverInterface::class)
            ->addTag('api_platform.graphql.mutation_resolver');
        $container->registerForAutoconfiguration(GraphQlTypeInterface::class)
            ->addTag('api_platform.graphql.type');
        $container->registerForAutoconfiguration(ErrorHandlerInterface::class)
            ->addTag('api_platform.graphql.error_handler');
    }

    private function registerLegacyBundlesConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        /** @var string[] $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['FOSUserBundle']) && $config['enable_fos_user']) {
            $loader->load('fos_user.xml');
        }

        if (isset($bundles['NelmioApiDocBundle']) && $config['enable_nelmio_api_doc']) {
            $loader->load('nelmio_api_doc.xml');

            $container->getDefinition('api_platform.nelmio_api_doc.annotations_provider')
                ->setDeprecated(...$this->buildDeprecationArgs('2.2', 'The "%service_id%" service is deprecated since API Platform 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.'));
            $container->getDefinition('api_platform.nelmio_api_doc.parser')
                ->setDeprecated(...$this->buildDeprecationArgs('2.2', 'The "%service_id%" service is deprecated since API Platform 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.'));
        }
    }

    private function registerCacheConfiguration(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.debug') || !$container->getParameter('kernel.debug')) {
            $container->removeDefinition('api_platform.cache_warmer.cache_pool_clearer');
        }

        if (!$container->hasParameter('api_platform.metadata_cache')) {
            return;
        }

        @trigger_error('The "api_platform.metadata_cache" parameter is deprecated since version 2.4 and will have no effect in 3.0.', E_USER_DEPRECATED);

        // BC
        if (!$container->getParameter('api_platform.metadata_cache')) {
            $container->removeDefinition('api_platform.cache_warmer.cache_pool_clearer');

            $container->register('api_platform.cache.metadata.property', ArrayAdapter::class);
            $container->register('api_platform.cache.metadata.resource', ArrayAdapter::class);
            $container->register('api_platform.cache.route_name_resolver', ArrayAdapter::class);
            $container->register('api_platform.cache.identifiers_extractor', ArrayAdapter::class);
            $container->register('api_platform.cache.subresource_operation_factory', ArrayAdapter::class);
            $container->register('api_platform.elasticsearch.cache.metadata.document', ArrayAdapter::class);
        }
    }

    private function registerDoctrineOrmConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['doctrine'])) {
            return;
        }

        $container->registerForAutoconfiguration(QueryItemExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.item');
        $container->registerForAutoconfiguration(DoctrineQueryCollectionExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.collection');
        $container->registerForAutoconfiguration(DoctrineOrmAbstractContextAwareFilter::class)
            ->setBindings(['$requestStack' => null]);

        $loader->load('doctrine_orm.xml');

        if ($this->isConfigEnabled($container, $config['eager_loading'])) {
            return;
        }

        $container->removeAlias(EagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading');
        $container->removeAlias(FilterEagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading');
    }

    private function registerDoctrineMongoDbOdmConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['doctrine_mongodb_odm'])) {
            return;
        }

        $container->registerForAutoconfiguration(AggregationItemExtensionInterface::class)
            ->addTag('api_platform.doctrine_mongodb.odm.aggregation_extension.item');
        $container->registerForAutoconfiguration(AggregationCollectionExtensionInterface::class)
            ->addTag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection');
        $container->registerForAutoconfiguration(DoctrineMongoDbOdmAbstractFilter::class)
            ->setBindings(['$managerRegistry' => new Reference('doctrine_mongodb')]);

        $loader->load('doctrine_mongodb_odm.xml');
    }

    private function registerHttpCacheConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $loader->load('http_cache.xml');

        if (!$this->isConfigEnabled($container, $config['http_cache']['invalidation'])) {
            return;
        }

        if ($this->isConfigEnabled($container, $config['doctrine'])) {
            $loader->load('doctrine_orm_http_cache_purger.xml');
        }

        $loader->load('http_cache_tags.xml');

        $definitions = [];
        foreach ($config['http_cache']['invalidation']['varnish_urls'] as $key => $url) {
            $definition = new ChildDefinition('api_platform.http_cache.purger.varnish_client');
            $definition->addArgument(['base_uri' => $url] + $config['http_cache']['invalidation']['request_options']);

            $definitions[] = $definition;
        }

        $container->getDefinition('api_platform.http_cache.purger.varnish')->setArguments([$definitions,
            $config['http_cache']['invalidation']['max_header_length'], ]);
        $container->setAlias('api_platform.http_cache.purger', 'api_platform.http_cache.purger.varnish');
    }

    /**
     * Normalizes the format from config to the one accepted by Symfony HttpFoundation.
     */
    private function getFormats(array $configFormats): array
    {
        $formats = [];
        foreach ($configFormats as $format => $value) {
            foreach ($value['mime_types'] as $mimeType) {
                $formats[$format][] = $mimeType;
            }
        }

        return $formats;
    }

    private function registerValidatorConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('validator.xml');

            $container->registerForAutoconfiguration(ValidationGroupsGeneratorInterface::class)
                ->addTag('api_platform.validation_groups_generator')
                ->setPublic(true); // this line should be removed in 3.0
            $container->registerForAutoconfiguration(PropertySchemaRestrictionMetadataInterface::class)
                ->addTag('api_platform.metadata.property_schema_restriction');
        }

        if (!$config['validator']) {
            return;
        }

        $container->setParameter('api_platform.validator.serialize_payload_fields', $config['validator']['serialize_payload_fields']);
    }

    private function registerDataCollectorConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$config['enable_profiler']) {
            return;
        }

        $loader->load('data_collector.xml');

        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('debug.xml');
        }
    }

    private function registerMercureConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['mercure'])) {
            return;
        }

        $loader->load('mercure.xml');
        $container->getDefinition('api_platform.mercure.listener.response.add_link_header')->addArgument($config['mercure']['hub_url'] ?? '%mercure.default_hub%');

        if ($this->isConfigEnabled($container, $config['doctrine'])) {
            $loader->load('doctrine_orm_mercure_publisher.xml');
        }
        if ($this->isConfigEnabled($container, $config['doctrine_mongodb_odm'])) {
            $loader->load('doctrine_mongodb_odm_mercure_publisher.xml');
        }

        if ($this->isConfigEnabled($container, $config['graphql'])) {
            $loader->load('graphql_mercure.xml');
            $container->getDefinition('api_platform.graphql.subscription.mercure_iri_generator')->addArgument($config['mercure']['hub_url'] ?? '%mercure.default_hub%');
        }
    }

    private function registerMessengerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['messenger'])) {
            return;
        }

        $loader->load('messenger.xml');
    }

    private function registerElasticsearchConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $enabled = $this->isConfigEnabled($container, $config['elasticsearch']);

        $container->setParameter('api_platform.elasticsearch.enabled', $enabled);

        if (!$enabled) {
            return;
        }

        $loader->load('elasticsearch.xml');

        $container->registerForAutoconfiguration(RequestBodySearchCollectionExtensionInterface::class)
            ->addTag('api_platform.elasticsearch.request_body_search_extension.collection');

        $container->setParameter('api_platform.elasticsearch.hosts', $config['elasticsearch']['hosts']);
        $container->setParameter('api_platform.elasticsearch.mapping', $config['elasticsearch']['mapping']);
    }

    private function registerDataTransformerConfiguration(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(DataTransformerInterface::class)
            ->addTag('api_platform.data_transformer');

        $container->registerForAutoconfiguration(DataTransformerInitializerInterface::class)
            ->addTag('api_platform.data_transformer');
    }

    private function registerSecurityConfiguration(ContainerBuilder $container, XmlFileLoader $loader): void
    {
        /** @var string[] $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['SecurityBundle'])) {
            $loader->load('security.xml');
        }
    }

    private function buildDeprecationArgs(string $version, string $message): array
    {
        return method_exists(Definition::class, 'getDeprecation')
            ? ['api-platform/core', $version, $message]
            : [true, $message];
    }
}
