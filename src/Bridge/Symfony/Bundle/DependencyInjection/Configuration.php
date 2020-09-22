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

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Version as DoctrineOrmVersion;
use Elasticsearch\Client as ElasticsearchClient;
use FOS\UserBundle\FOSUserBundle;
use GraphQL\GraphQL;
use Symfony\Bundle\FullStack;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * The configuration of the bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('api_platform');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('api_platform');
        }

        $rootNode
            ->beforeNormalization()
                ->ifTrue(static function ($v) {
                    return false === ($v['enable_swagger'] ?? null);
                })
                ->then(static function ($v) {
                    $v['swagger']['versions'] = [];

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('title')
                    ->info('The title of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
                ->scalarNode('description')
                    ->info('The description of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
                ->scalarNode('version')
                    ->info('The version of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('0.0.0')
                ->end()
                ->booleanNode('show_webby')->defaultTrue()->info('If true, show Webby on the documentation page')->end()
                ->scalarNode('default_operation_path_resolver')
                    ->defaultValue('api_platform.operation_path_resolver.underscore')
                    ->setDeprecated(...$this->buildDeprecationArgs('2.1', 'The use of the `default_operation_path_resolver` has been deprecated in 2.1 and will be removed in 3.0. Use `path_segment_name_generator` instead.'))
                    ->info('Specify the default operation path resolver to use for generating resources operations path.')
                ->end()
                ->scalarNode('name_converter')->defaultNull()->info('Specify a name converter to use.')->end()
                ->scalarNode('path_segment_name_generator')->defaultValue('api_platform.path_segment_name_generator.underscore')->info('Specify a path name generator to use.')->end()
                ->booleanNode('allow_plain_identifiers')->defaultFalse()->info('Allow plain identifiers, for example "id" instead of "@id" when denormalizing a relation.')->end()
                ->arrayNode('validator')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->variableNode('serialize_payload_fields')->defaultValue([])->info('Set to null to serialize all payload fields when a validation error is thrown, or set the fields you want to include explicitly.')->end()
                    ->end()
                ->end()
                ->arrayNode('eager_loading')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('fetch_partial')->defaultFalse()->info('Fetch only partial data according to serialization groups. If enabled, Doctrine ORM entities will not work as expected if any of the other fields are used.')->end()
                        ->integerNode('max_joins')->defaultValue(30)->info('Max number of joined relations before EagerLoading throws a RuntimeException')->end()
                        ->booleanNode('force_eager')->defaultTrue()->info('Force join on every relation. If disabled, it will only join relations having the EAGER fetch mode.')->end()
                    ->end()
                ->end()
                ->booleanNode('enable_fos_user')->defaultValue(class_exists(FOSUserBundle::class))->info('Enable the FOSUserBundle integration.')->end()
                ->booleanNode('enable_nelmio_api_doc')
                    ->defaultFalse()
                    ->setDeprecated(...$this->buildDeprecationArgs('2.2', 'Enabling the NelmioApiDocBundle integration has been deprecated in 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.'))
                    ->info('Enable the NelmioApiDocBundle integration.')
                ->end()
                ->booleanNode('enable_swagger')->defaultTrue()->info('Enable the Swagger documentation and export.')->end()
                ->booleanNode('enable_swagger_ui')->defaultValue(class_exists(TwigBundle::class))->info('Enable Swagger UI')->end()
                ->booleanNode('enable_re_doc')->defaultValue(class_exists(TwigBundle::class))->info('Enable ReDoc')->end()
                ->booleanNode('enable_entrypoint')->defaultTrue()->info('Enable the entrypoint')->end()
                ->booleanNode('enable_docs')->defaultTrue()->info('Enable the docs')->end()
                ->booleanNode('enable_profiler')->defaultTrue()->info('Enable the data collector and the WebProfilerBundle integration.')->end()
                ->arrayNode('collection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('exists_parameter_name')->defaultValue('exists')->cannotBeEmpty()->info('The name of the query parameter to filter on nullable field values.')->end()
                        ->scalarNode('order')->defaultValue('ASC')->info('The default order of results.')->end() // Default ORDER is required for postgresql and mysql >= 5.7 when using LIMIT/OFFSET request
                        ->scalarNode('order_parameter_name')->defaultValue('order')->cannotBeEmpty()->info('The name of the query parameter to order results.')->end()
                        ->arrayNode('pagination')
                            ->canBeDisabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->setDeprecated('The use of the `collection.pagination.enabled` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.pagination_enabled` instead.')
                                    ->defaultTrue()
                                    ->info('To enable or disable pagination for all resource collections by default.')
                                ->end()
                                ->booleanNode('partial')
                                    ->setDeprecated('The use of the `collection.pagination.partial` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.pagination_partial` instead.')
                                    ->defaultFalse()
                                    ->info('To enable or disable partial pagination for all resource collections by default when pagination is enabled.')
                                ->end()
                                ->booleanNode('client_enabled')
                                    ->setDeprecated('The use of the `collection.pagination.client_enabled` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.pagination_client_enabled` instead.')
                                    ->defaultFalse()
                                    ->info('To allow the client to enable or disable the pagination.')
                                ->end()
                                ->booleanNode('client_items_per_page')
                                    ->setDeprecated('The use of the `collection.pagination.client_items_per_page` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.pagination_client_items_per_page` instead.')
                                    ->defaultFalse()
                                    ->info('To allow the client to set the number of items per page.')
                                ->end()
                                ->booleanNode('client_partial')
                                    ->setDeprecated('The use of the `collection.pagination.client_partial` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.pagination_client_partial` instead.')
                                    ->defaultFalse()
                                    ->info('To allow the client to enable or disable partial pagination.')
                                ->end()
                                ->integerNode('items_per_page')
                                    ->setDeprecated('The use of the `collection.pagination.items_per_page` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.pagination_items_per_page` instead.')
                                    ->defaultValue(30)
                                    ->info('The default number of items per page.')
                                ->end()
                                ->integerNode('maximum_items_per_page')
                                    ->setDeprecated('The use of the `collection.pagination.maximum_items_per_page` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.pagination_maximum_items_per_page` instead.')
                                    ->defaultNull()
                                    ->info('The maximum number of items per page.')
                                ->end()
                                ->scalarNode('page_parameter_name')->defaultValue('page')->cannotBeEmpty()->info('The default name of the parameter handling the page number.')->end()
                                ->scalarNode('enabled_parameter_name')->defaultValue('pagination')->cannotBeEmpty()->info('The name of the query parameter to enable or disable pagination.')->end()
                                ->scalarNode('items_per_page_parameter_name')->defaultValue('itemsPerPage')->cannotBeEmpty()->info('The name of the query parameter to set the number of items per page.')->end()
                                ->scalarNode('partial_parameter_name')->defaultValue('partial')->cannotBeEmpty()->info('The name of the query parameter to enable or disable partial pagination.')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mapping')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('paths')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('resource_class_directories')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        $this->addRoutingLoaderSection($rootNode);
        $this->addDoctrineOrmSection($rootNode);
        $this->addDoctrineMongoDbOdmSection($rootNode);
        $this->addOAuthSection($rootNode);
        $this->addGraphQlSection($rootNode);
        $this->addSwaggerSection($rootNode);
        $this->addHttpCacheSection($rootNode);
        $this->addMercureSection($rootNode);
        $this->addMessengerSection($rootNode);
        $this->addElasticsearchSection($rootNode);

        $this->addExceptionToStatusSection($rootNode);

        $this->addFormatSection($rootNode, 'formats', [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'json' => ['mime_types' => ['application/json']], // Swagger support
            'html' => ['mime_types' => ['text/html']], // Swagger UI support
        ]);
        $this->addFormatSection($rootNode, 'patch_formats', []);
        $this->addFormatSection($rootNode, 'error_formats', [
            'jsonproblem' => ['mime_types' => ['application/problem+json']],
            'jsonld' => ['mime_types' => ['application/ld+json']],
        ]);

        $this->addDefaultsSection($rootNode);

        return $treeBuilder;
    }

    private function addRoutingLoaderSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('routing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('autoload')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('externals')->defaultTrue()->end()
                                ->booleanNode('directories')->defaultTrue()->end()
                                ->booleanNode('resources')->defaultTrue()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('autoload')->defaultTrue()->end()
                        ->arrayNode('sources')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('configs')->defaultTrue()->end()
                                ->booleanNode('doctrine')->defaultTrue()->end()
                                ->booleanNode('mongodb')->defaultTrue()->end()
                            ->end()
                        ->end()
                        ->arrayNode('bundles')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('include')
                                    ->scalarPrototype()->end()
                                ->end()
                                ->arrayNode('exclude')
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addDoctrineOrmSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('doctrine')
                    ->{class_exists(DoctrineBundle::class) && class_exists(DoctrineOrmVersion::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }

    private function addDoctrineMongoDbOdmSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('doctrine_mongodb_odm')
                    ->{class_exists(DoctrineMongoDBBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }

    private function addOAuthSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('oauth')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('clientId')->defaultValue('')->info('The oauth client id.')->end()
                        ->scalarNode('clientSecret')->defaultValue('')->info('The oauth client secret.')->end()
                        ->scalarNode('type')->defaultValue('oauth2')->info('The oauth client secret.')->end()
                        ->scalarNode('flow')->defaultValue('application')->info('The oauth flow grant type.')->end()
                        ->scalarNode('tokenUrl')->defaultValue('/oauth/v2/token')->info('The oauth token url.')->end()
                        ->scalarNode('authorizationUrl')->defaultValue('/oauth/v2/auth')->info('The oauth authentication url.')->end()
                        ->scalarNode('refreshUrl')->defaultValue('/oauth/v2/refresh')->info('The oauth refresh url.')->end()
                        ->arrayNode('scopes')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addGraphQlSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('graphql')
                    ->{class_exists(GraphQL::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_ide')->defaultValue('graphiql')->end()
                        ->arrayNode('graphiql')
                            ->{class_exists(GraphQL::class) && class_exists(TwigBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                        ->end()
                        ->arrayNode('graphql_playground')
                            ->{class_exists(GraphQL::class) && class_exists(TwigBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                        ->end()
                        ->scalarNode('nesting_separator')->defaultValue('_')->info('The separator to use to filter nested fields.')->end()
                        ->arrayNode('collection')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('pagination')
                                    ->canBeDisabled()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addSwaggerSection(ArrayNodeDefinition $rootNode): void
    {
        $defaultVersions = [2, 3];

        $rootNode
            ->children()
                ->arrayNode('swagger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('versions')
                            ->info('The active versions of Open API to be exported or used in the swagger_ui. The first value is the default.')
                            ->defaultValue($defaultVersions)
                            ->beforeNormalization()
                                ->always(static function ($v) {
                                    if (!\is_array($v)) {
                                        $v = [$v];
                                    }

                                    foreach ($v as &$version) {
                                        $version = (int) $version;
                                    }

                                    return $v;
                                })
                            ->end()
                            ->validate()
                                ->ifTrue(function ($v) use ($defaultVersions) {
                                    return $v !== array_intersect($v, $defaultVersions);
                                })
                                ->thenInvalid(sprintf('Only the versions %s are supported. Got %s.', implode(' and ', $defaultVersions), '%s'))
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('api_keys')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('name')
                                        ->info('The name of the header or query parameter containing the api key.')
                                    ->end()
                                    ->enumNode('type')
                                        ->info('Whether the api key should be a query parameter or a header.')
                                        ->values(['query', 'header'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addHttpCacheSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('http_cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('etag')
                            ->setDeprecated('The use of the `http_cache.etag` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.cache_headers.etag` instead.')
                            ->defaultTrue()
                            ->info('Automatically generate etags for API responses.')
                        ->end()
                        ->integerNode('max_age')
                            ->setDeprecated('The use of the `http_cache.max_age` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.cache_headers.max_age` instead.')
                            ->defaultNull()
                            ->info('Default value for the response max age.')
                        ->end()
                        ->integerNode('shared_max_age')
                            ->setDeprecated('The use of the `http_cache.shared_max_age` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.cache_headers.shared_max_age` instead.')
                            ->defaultNull()
                            ->info('Default value for the response shared (proxy) max age.')
                        ->end()
                        ->arrayNode('vary')
                            ->setDeprecated('The use of the `http_cache.vary` has been deprecated in 2.6 and will be removed in 3.0. Use `defaults.cache_headers.vary` instead.')
                            ->defaultValue(['Accept'])
                            ->prototype('scalar')->end()
                            ->info('Default values of the "Vary" HTTP header.')
                        ->end()
                        ->booleanNode('public')->defaultNull()->info('To make all responses public by default.')->end()
                        ->arrayNode('invalidation')
                            ->info('Enable the tags-based cache invalidation system.')
                            ->canBeEnabled()
                            ->children()
                                ->arrayNode('varnish_urls')
                                    ->defaultValue([])
                                    ->prototype('scalar')->end()
                                    ->info('URLs of the Varnish servers to purge using cache tags when a resource is updated.')
                                ->end()
                                ->integerNode('max_header_length')
                                    ->defaultValue(7500)
                                    ->info('Max header length supported by the server')
                                ->end()
                                ->variableNode('request_options')
                                    ->defaultValue([])
                                    ->validate()
                                        ->ifTrue(function ($v) { return false === \is_array($v); })
                                        ->thenInvalid('The request_options parameter must be an array.')
                                    ->end()
                                    ->info('To pass options to the client charged with the request.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addMercureSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('mercure')
                    ->{class_exists(MercureBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->scalarNode('hub_url')
                            ->defaultNull()
                            ->info('The URL sent in the Link HTTP header. If not set, will default to the URL for MercureBundle\'s default hub.')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addMessengerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('messenger')
                    ->{!class_exists(FullStack::class) && interface_exists(MessageBusInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }

    private function addElasticsearchSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('elasticsearch')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                            ->validate()
                                ->ifTrue()
                                ->then(static function (bool $v): bool {
                                    if (!class_exists(ElasticsearchClient::class)) {
                                        throw new InvalidConfigurationException('The elasticsearch/elasticsearch package is required for Elasticsearch support.');
                                    }

                                    return $v;
                                })
                            ->end()
                        ->end()
                        ->arrayNode('hosts')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('mapping')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('resource_class')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('index')->defaultNull()->end()
                                    ->scalarNode('type')->defaultValue(DocumentMetadata::DEFAULT_TYPE)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function addExceptionToStatusSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('exception_to_status')
                    ->defaultValue([
                        SerializerExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                        InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                        FilterValidationException::class => Response::HTTP_BAD_REQUEST,
                        OptimisticLockException::class => Response::HTTP_CONFLICT,
                    ])
                    ->info('The list of exceptions mapped to their HTTP status code.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('exception_class')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function (array $exceptionToStatus) {
                            foreach ($exceptionToStatus as &$httpStatusCode) {
                                if (\is_int($httpStatusCode)) {
                                    continue;
                                }

                                if (\defined($httpStatusCodeConstant = sprintf('%s::%s', Response::class, $httpStatusCode))) {
                                    @trigger_error(sprintf('Using a string "%s" as a constant of the "%s" class is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3. Use the Symfony\'s custom YAML extension for PHP constants instead (i.e. "!php/const %s").', $httpStatusCode, Response::class, $httpStatusCodeConstant), E_USER_DEPRECATED);

                                    $httpStatusCode = \constant($httpStatusCodeConstant);
                                }
                            }

                            return $exceptionToStatus;
                        })
                    ->end()
                    ->prototype('integer')->end()
                    ->validate()
                        ->ifArray()
                        ->then(function (array $exceptionToStatus) {
                            foreach ($exceptionToStatus as $httpStatusCode) {
                                if ($httpStatusCode < 100 || $httpStatusCode >= 600) {
                                    throw new InvalidConfigurationException(sprintf('The HTTP status code "%s" is not valid.', $httpStatusCode));
                                }
                            }

                            return $exceptionToStatus;
                        })
                    ->end()
                ->end()
            ->end();
    }

    private function addFormatSection(ArrayNodeDefinition $rootNode, string $key, array $defaultValue): void
    {
        $rootNode
            ->children()
                ->arrayNode($key)
                    ->defaultValue($defaultValue)
                    ->info('The list of enabled formats. The first one will be the default.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('format')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function ($v) {
                            foreach ($v as $format => $value) {
                                if (isset($value['mime_types'])) {
                                    continue;
                                }

                                $v[$format] = ['mime_types' => $value];
                            }

                            return $v;
                        })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->arrayNode('mime_types')->prototype('scalar')->end()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addDefaultsSection(ArrayNodeDefinition $rootNode): void
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $defaultsNode = $rootNode->children()->arrayNode('defaults');

        $defaultsNode
            ->ignoreExtraKeys()
            ->beforeNormalization()
            ->always(static function (array $defaults) use ($nameConverter) {
                $normalizedDefaults = [];
                foreach ($defaults as $option => $value) {
                    $option = $nameConverter->normalize($option);
                    $normalizedDefaults[$option] = $value;
                }

                return $normalizedDefaults;
            });

        foreach (ApiResource::CONFIGURABLE_DEFAULTS as $attribute) {
            $snakeCased = $nameConverter->normalize($attribute);
            $defaultsNode->children()->variableNode($snakeCased);
        }
    }

    private function buildDeprecationArgs(string $version, string $message): array
    {
        return method_exists(BaseNode::class, 'getDeprecation')
            ? ['api-platform/core', $version, $message]
            : [$message];
    }
}
