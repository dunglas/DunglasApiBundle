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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FOS\UserBundle\FOSUserBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Exception\Doubler\MethodNotFoundException;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformExtensionTest extends TestCase
{
    const DEFAULT_CONFIG = ['api_platform' => [
        'title' => 'title',
        'description' => 'description',
        'version' => 'version',
        'formats' => [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonhal' => ['mime_types' => ['application/hal+json']],
        ],
        'http_cache' => ['invalidation' => [
            'enabled' => true,
            'varnish_urls' => ['test'],
        ]],
    ]];

    private $extension;

    protected function setUp()
    {
        $this->extension = new ApiPlatformExtension();
    }

    public function tearDown()
    {
        unset($this->extension);
    }

    public function testConstruct()
    {
        $this->extension = new ApiPlatformExtension();

        $this->assertInstanceOf(PrependExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ConfigurationExtensionInterface::class, $this->extension);
    }

    public function testNotPrependWhenNull()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(null)->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldNotBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('api_platform', Argument::type('array'))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testNotPrependSerializerWhenConfigExist()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['serializer' => ['enabled' => false]]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::any())->willReturn(null)->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::that(function (array $config) {
            return array_key_exists('serializer', $config);
        }))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testNotPrependPropertyInfoWhenConfigExist()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['property_info' => ['enabled' => false]]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::any())->willReturn(null)->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::that(function (array $config) {
            return array_key_exists('property_info', $config);
        }))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testPrependWhenNotConfigured()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testPrependWhenNotEnabled()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['serializer' => []]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testPrependWhenNameConverterIsConfigured()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['serializer' => ['enabled' => true, 'name_converter' => 'foo'], 'property_info' => ['enabled' => false]]]);
        $containerBuilderProphecy->prependExtensionConfig('api_platform', ['name_converter' => 'foo'])->shouldBeCalled();

        $this->extension->prepend($containerBuilderProphecy->reveal());
    }

    public function testNotPrependWhenNameConverterIsNotConfigured()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['serializer' => ['enabled' => true], 'property_info' => ['enabled' => false]]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('api_platform', Argument::type('array'))->shouldNotBeCalled();

        $this->extension->prepend($containerBuilderProphecy->reveal());
    }

    public function testLoadDefaultConfig()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testSetNameConverter()
    {
        $nameConverterId = 'test.name_converter';

        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', $nameConverterId)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['name_converter' => $nameConverterId]]), $containerBuilder);
    }

    public function testEnableFosUser()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'FOSUserBundle' => FOSUserBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.fos_user.event_listener', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['enable_fos_user' => true]]), $containerBuilder);
    }

    public function testFosUserPriority()
    {
        $builder = new ContainerBuilder();

        $loader = new XmlFileLoader($builder, new FileLocator(dirname(__DIR__).'/../../../../src/Bridge/Symfony/Bundle/Resources/config'));
        $loader->load('api.xml');
        $loader->load('fos_user.xml');

        $fosListener = $builder->getDefinition('api_platform.fos_user.event_listener');
        $viewListener = $builder->getDefinition('api_platform.listener.view.serialize');

        // Ensure FOSUser event listener priority is always greater than the view serialize listener
        $this->assertGreaterThan(
            $viewListener->getTag('kernel.event_listener')[0]['priority'],
            $fosListener->getTag('kernel.event_listener')[0]['priority'],
            'api_platform.fos_user.event_listener priority needs to be greater than that of api_platform.listener.view.serialize'
        );
    }

    public function testEnableNelmioApiDoc()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'NelmioApiDocBundle' => NelmioApiDocBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.nelmio_api_doc.annotations_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.nelmio_api_doc.parser', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['enable_nelmio_api_doc' => true]]), $containerBuilder);
    }

    public function testEnableSecurity()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'SecurityBundle' => SecurityBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.security.listener.request.deny_access', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.security.expression_language', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessageRegExp /Unsupported mapping type in ".+", supported types are XML & Yaml\./
     */
    public function testResourcesToWatchWithUnsupportedMappingType()
    {
        $this->extension->load(
            array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['mapping' => ['paths' => [__FILE__]]]]),
            $this->getPartialContainerBuilderProphecy(false)->reveal()
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage Could not open file or directory "fake_file.xml".
     */
    public function testResourcesToWatchWithNonExistentFile()
    {
        $this->extension->load(
            array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['mapping' => ['paths' => ['fake_file.xml']]]]),
            $this->getPartialContainerBuilderProphecy()->reveal()
        );
    }

    public function testDisableEagerLoadingExtension()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setParameter('api_platform.eager_loading.enabled', false)->shouldBeCalled();
        $containerBuilderProphecy->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading')->shouldBeCalled();
        $containerBuilderProphecy->removeDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading')->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();
        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['eager_loading' => ['enabled' => false]]]), $containerBuilder);
    }

    public function testNotRegisterHttpCacheWhenEnabledWithNoVarnishServer()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['http_cache']['invalidation']['varnish_urls'] = [];

        $this->extension->load($config, $containerBuilder);
    }

    private function getPartialContainerBuilderProphecy($test = false)
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $childDefinitionProphecy = $this->prophesize(ChildDefinition::class);

        $containerBuilderProphecy->registerForAutoconfiguration(ItemDataProviderInterface::class)
            ->willReturn($childDefinitionProphecy)->shouldBeCalledTimes(1);
        $childDefinitionProphecy->addTag('api_platform.item_data_provider')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(CollectionDataProviderInterface::class)
            ->willReturn($childDefinitionProphecy)->shouldBeCalledTimes(1);
        $childDefinitionProphecy->addTag('api_platform.collection_data_provider')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(QueryItemExtensionInterface::class)
            ->willReturn($childDefinitionProphecy)->shouldBeCalledTimes(1);
        $childDefinitionProphecy->addTag('api_platform.doctrine.orm.query_extension.item')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(QueryCollectionExtensionInterface::class)
            ->willReturn($childDefinitionProphecy)->shouldBeCalledTimes(1);
        $childDefinitionProphecy->addTag('api_platform.doctrine.orm.query_extension.collection')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(FilterInterface::class)
            ->willReturn($childDefinitionProphecy)->shouldBeCalledTimes(1);
        $childDefinitionProphecy->addTag('api_platform.filter')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
        ])->shouldBeCalled();

        $containerBuilderProphecy->getParameter('kernel.bundles_metadata')->willReturn([
            'TestBundle' => [
                'parent' => null,
                'path' => realpath(__DIR__.'/../../../../Fixtures/TestBundle'),
                'namespace' => TestBundle::class,
            ],
        ])->shouldBeCalled();

        $containerBuilderProphecy->fileExists(Argument::type('string'), false)->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        $parameters = [
            'api_platform.collection.order' => 'ASC',
            'api_platform.collection.order_parameter_name' => 'order',
            'api_platform.collection.pagination.client_enabled' => false,
            'api_platform.collection.pagination.client_items_per_page' => false,
            'api_platform.collection.pagination.enabled' => true,
            'api_platform.collection.pagination.enabled_parameter_name' => 'pagination',
            'api_platform.collection.pagination.items_per_page' => 30,
            'api_platform.collection.pagination.items_per_page_parameter_name' => 'itemsPerPage',
            'api_platform.collection.pagination.maximum_items_per_page' => null,
            'api_platform.collection.pagination.page_parameter_name' => 'page',
            'api_platform.description' => 'description',
            'api_platform.error_formats' => ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']],
            'api_platform.formats' => ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']],
            'api_platform.exception_to_status' => [ExceptionInterface::class => Response::HTTP_BAD_REQUEST, InvalidArgumentException::class => Response::HTTP_BAD_REQUEST],
            'api_platform.title' => 'title',
            'api_platform.version' => 'version',
            'api_platform.eager_loading.enabled' => Argument::type('bool'),
            'api_platform.eager_loading.max_joins' => 30,
            'api_platform.eager_loading.force_eager' => true,
            'api_platform.eager_loading.fetch_partial' => false,
            'api_platform.http_cache.etag' => true,
            'api_platform.http_cache.max_age' => null,
            'api_platform.http_cache.shared_max_age' => null,
            'api_platform.http_cache.vary' => ['Accept'],
            'api_platform.http_cache.public' => null,
        ];

        foreach ($parameters as $key => $value) {
            $containerBuilderProphecy->setParameter($key, $value)->shouldBeCalled();
        }

        $containerBuilderProphecy->fileExists(Argument::type('string'))->shouldBeCalled();

        try {
            $containerBuilderProphecy->fileExists(Argument::type('string'))->shouldBeCalled();
        } catch (MethodNotFoundException $e) {
            $containerBuilderProphecy->addResource(Argument::type(ResourceInterface::class))->shouldBeCalled();
        }

        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->shouldBeCalled();

        $definitions = [
            'api_platform.action.documentation',
            'api_platform.action.placeholder',
            'api_platform.action.entrypoint',
            'api_platform.action.exception',
            'api_platform.action.placeholder',
            'api_platform.cache.metadata.property',
            'api_platform.cache.identifiers_extractor',
            'api_platform.cache.metadata.resource',
            'api_platform.cache.route_name_resolver',
            'api_platform.cache.subresource_operation_factory',
            'api_platform.collection_data_provider',
            'api_platform.filter_locator',
            'api_platform.filter_collection_factory',
            'api_platform.filters',
            'api_platform.iri_converter',
            'api_platform.item_data_provider',
            'api_platform.listener.exception',
            'api_platform.listener.exception.validation',
            'api_platform.listener.request.add_format',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.request.read',
            'api_platform.listener.view.respond',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.validate',
            'api_platform.metadata.extractor.xml',
            'api_platform.metadata.property.metadata_factory.cached',
            'api_platform.metadata.property.metadata_factory.inherited',
            'api_platform.metadata.property.metadata_factory.property_info',
            'api_platform.metadata.property.metadata_factory.serializer',
            'api_platform.metadata.property.metadata_factory.validator',
            'api_platform.metadata.property.metadata_factory.xml',
            'api_platform.metadata.property.name_collection_factory.cached',
            'api_platform.metadata.property.name_collection_factory.inherited',
            'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.property.name_collection_factory.xml',
            'api_platform.metadata.resource.metadata_factory.cached',
            'api_platform.metadata.resource.metadata_factory.operation',
            'api_platform.metadata.resource.metadata_factory.short_name',
            'api_platform.metadata.resource.metadata_factory.xml',
            'api_platform.metadata.resource.name_collection_factory.cached',
            'api_platform.metadata.resource.name_collection_factory.xml',
            'api_platform.identifiers_extractor',
            'api_platform.identifiers_extractor.cached',
            'api_platform.negotiator',
            'api_platform.operation_method_resolver',
            'api_platform.operation_path_resolver.custom',
            'api_platform.operation_path_resolver.dash',
            'api_platform.operation_path_resolver.router',
            'api_platform.operation_path_resolver.generator',
            'api_platform.operation_path_resolver.underscore',
            'api_platform.path_segment_name_generator.underscore',
            'api_platform.path_segment_name_generator.dash',
            'api_platform.resource_class_resolver',
            'api_platform.route_loader',
            'api_platform.route_name_resolver',
            'api_platform.route_name_resolver.cached',
            'api_platform.router',
            'api_platform.serializer.context_builder',
            'api_platform.serializer.context_builder.filter',
            'api_platform.serializer.property_filter',
            'api_platform.serializer.group_filter',
            'api_platform.serializer.normalizer.item',
            'api_platform.subresource_data_provider',
            'api_platform.subresource_operation_factory',
            'api_platform.subresource_operation_factory.cached',
        ];

        foreach ($definitions as $definition) {
            $containerBuilderProphecy->setDefinition($definition, Argument::type(Definition::class))->shouldBeCalled();
        }

        $aliases = [
            'api_platform.action.delete_item' => 'api_platform.action.placeholder',
            'api_platform.action.get_collection' => 'api_platform.action.placeholder',
            'api_platform.action.get_item' => 'api_platform.action.placeholder',
            'api_platform.action.get_subresource' => 'api_platform.action.placeholder',
            'api_platform.action.post_collection' => 'api_platform.action.placeholder',
            'api_platform.action.put_item' => 'api_platform.action.placeholder',
            'api_platform.metadata.property.metadata_factory' => 'api_platform.metadata.property.metadata_factory.xml',
            'api_platform.metadata.property.name_collection_factory' => 'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.resource.metadata_factory' => 'api_platform.metadata.resource.metadata_factory.xml',
            'api_platform.metadata.resource.name_collection_factory' => 'api_platform.metadata.resource.name_collection_factory.xml',
            'api_platform.operation_path_resolver' => 'api_platform.operation_path_resolver.router',
            'api_platform.operation_path_resolver.default' => 'api_platform.operation_path_resolver.underscore',
            'api_platform.path_segment_name_generator' => 'api_platform.path_segment_name_generator.underscore',
            'api_platform.property_accessor' => 'property_accessor',
            'api_platform.property_info' => 'property_info',
            'api_platform.serializer' => 'serializer',
            IriConverterInterface::class => 'api_platform.iri_converter',
            SerializerContextBuilderInterface::class => 'api_platform.serializer.context_builder',
            CollectionDataProviderInterface::class => 'api_platform.collection_data_provider',
            ItemDataProviderInterface::class => 'api_platform.item_data_provider',
            SubresourceDataProviderInterface::class => 'api_platform.subresource_data_provider',
            ResourceNameCollectionFactoryInterface::class => 'api_platform.metadata.resource.name_collection_factory',
            ResourceMetadataFactoryInterface::class => 'api_platform.metadata.resource.metadata_factory',
            PropertyNameCollectionFactoryInterface::class => 'api_platform.metadata.property.name_collection_factory',
            PropertyMetadataFactoryInterface::class => 'api_platform.metadata.property.metadata_factory',
        ];

        foreach ($aliases as $alias => $service) {
            $containerBuilderProphecy->setAlias($alias, $service)->shouldBeCalled();
        }

        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);
        $containerBuilderProphecy->getDefinition('api_platform.http_cache.purger.varnish')->willReturn(new Definition());

        return $containerBuilderProphecy;
    }

    private function getBaseContainerBuilderProphecy()
    {
        $containerBuilderProphecy = $this->getPartialContainerBuilderProphecy();

        $containerBuilderProphecy->addResource(Argument::type(DirectoryResource::class))->shouldBeCalled();

        $parameters = [
            'api_platform.oauth.enabled' => false,
            'api_platform.oauth.clientId' => '',
            'api_platform.oauth.clientSecret' => '',
            'api_platform.oauth.type' => 'oauth2',
            'api_platform.oauth.flow' => 'application',
            'api_platform.oauth.tokenUrl' => '/oauth/v2/token',
            'api_platform.oauth.authorizationUrl' => '/oauth/v2/auth',
            'api_platform.oauth.scopes' => [],
            'api_platform.swagger.api_keys' => [],
            'api_platform.enable_swagger' => true,
            'api_platform.enable_swagger_ui' => true,
            'api_platform.resource_class_directories' => Argument::type('array'),
        ];

        foreach ($parameters as $key => $value) {
            $containerBuilderProphecy->setParameter($key, $value)->shouldBeCalled();
        }

        foreach (['yaml', 'xml'] as $format) {
            $definitionProphecy = $this->prophesize(Definition::class);
            $definitionProphecy->addArgument(Argument::type('array'))->shouldBeCalled();
            $containerBuilderProphecy->getDefinition('api_platform.metadata.extractor.'.$format)->willReturn($definitionProphecy->reveal())->shouldBeCalled();
        }

        $definitions = [
            'api_platform.doctrine.listener.view.write',
            'api_platform.doctrine.metadata_factory',
            'api_platform.doctrine.orm.boolean_filter',
            'api_platform.doctrine.orm.collection_data_provider',
            'api_platform.doctrine.orm.date_filter',
            'api_platform.doctrine.orm.default.collection_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider',
            'api_platform.doctrine.orm.exists_filter',
            'api_platform.doctrine.orm.default.subresource_data_provider',
            'api_platform.doctrine.orm.item_data_provider',
            'api_platform.doctrine.orm.metadata.property.metadata_factory',
            'api_platform.doctrine.orm.numeric_filter',
            'api_platform.doctrine.orm.order_filter',
            'api_platform.doctrine.orm.query_extension.eager_loading',
            'api_platform.doctrine.orm.query_extension.filter',
            'api_platform.doctrine.orm.query_extension.filter_eager_loading',
            'api_platform.doctrine.orm.query_extension.order',
            'api_platform.doctrine.orm.query_extension.pagination',
            'api_platform.doctrine.orm.range_filter',
            'api_platform.doctrine.orm.search_filter',
            'api_platform.doctrine.orm.subresource_data_provider',
            'api_platform.doctrine.listener.http_cache.purge',
            'api_platform.doctrine.listener.view.write',
            'api_platform.jsonld.normalizer.item',
            'api_platform.jsonld.encoder',
            'api_platform.jsonld.action.context',
            'api_platform.jsonld.context_builder',
            'api_platform.jsonld.normalizer.item',
            'api_platform.swagger.normalizer.documentation',
            'api_platform.swagger.command.swagger_command',
            'api_platform.swagger.action.ui',
            'api_platform.swagger.listener.ui',
            'api_platform.hal.encoder',
            'api_platform.hal.normalizer.collection',
            'api_platform.hal.normalizer.entrypoint',
            'api_platform.hal.normalizer.item',
            'api_platform.hydra.listener.response.add_link_header',
            'api_platform.hydra.normalizer.collection',
            'api_platform.hydra.normalizer.collection_filters',
            'api_platform.hydra.normalizer.constraint_violation_list',
            'api_platform.hydra.normalizer.documentation',
            'api_platform.hydra.normalizer.entrypoint',
            'api_platform.hydra.normalizer.error',
            'api_platform.hydra.normalizer.partial_collection_view',
            'api_platform.jsonld.action.context',
            'api_platform.jsonld.context_builder',
            'api_platform.jsonld.encoder',
            'api_platform.jsonld.normalizer.item',
            'api_platform.jsonld.normalizer.item',
            'api_platform.metadata.extractor.yaml',
            'api_platform.metadata.property.metadata_factory.annotation',
            'api_platform.metadata.property.metadata_factory.yaml',
            'api_platform.metadata.property.name_collection_factory.yaml',
            'api_platform.metadata.resource.metadata_factory.annotation',
            'api_platform.metadata.resource.metadata_factory.operation',
            'api_platform.metadata.resource.metadata_factory.php_doc',
            'api_platform.metadata.resource.metadata_factory.short_name',
            'api_platform.metadata.resource.metadata_factory.yaml',
            'api_platform.metadata.resource.name_collection_factory.annotation',
            'api_platform.metadata.resource.name_collection_factory.yaml',
            'api_platform.metadata.subresource.metadata_factory.annotation',
            'api_platform.problem.encoder',
            'api_platform.problem.normalizer.constraint_violation_list',
            'api_platform.problem.normalizer.error',
            'api_platform.swagger.action.ui',
            'api_platform.swagger.command.swagger_command',
            'api_platform.swagger.normalizer.documentation',
            'api_platform.http_cache.listener.response.configure',
            'api_platform.http_cache.purger.varnish',
            'api_platform.http_cache.purger.varnish_client',
            'api_platform.http_cache.listener.response.add_tags',
            'api_platform.http_cache.clearer.varnish',
        ];

        foreach ($definitions as $definition) {
            $containerBuilderProphecy->setDefinition($definition, Argument::type(Definition::class))->shouldBeCalled();
        }

        $aliases = [
            'api_platform.http_cache.purger' => 'api_platform.http_cache.purger.varnish',
            EagerLoadingExtension::class => 'api_platform.doctrine.orm.query_extension.eager_loading',
            FilterExtension::class => 'api_platform.doctrine.orm.query_extension.filter',
            FilterEagerLoadingExtension::class => 'api_platform.doctrine.orm.query_extension.filter_eager_loading',
            PaginationExtension::class => 'api_platform.doctrine.orm.query_extension.pagination',
            OrderExtension::class => 'api_platform.doctrine.orm.query_extension.order',
        ];

        foreach ($aliases as $alias => $service) {
            $containerBuilderProphecy->setAlias($alias, $service)->shouldBeCalled();
        }

        return $containerBuilderProphecy;
    }
}
