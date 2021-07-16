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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\ApiLoader;
use ApiPlatform\Core\Exception\InvalidResourceException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactory;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolver;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @group legacy
 */
class LegacyApiLoaderTest extends TestCase
{
    use ProphecyTrait;

    public function testLegacyApiLoader()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withShortName('dummy');
        $resourceMetadata = $resourceMetadata->withAttributes(['identifiers' => 'id']);
        //default operation based on OperationResourceMetadataFactory
        $resourceMetadata = $resourceMetadata->withItemOperations([
            'get' => ['method' => 'GET', 'requirements' => ['id' => '\d+'], 'defaults' => ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'], 'stateless' => null],
            'put' => ['method' => 'PUT', 'stateless' => null],
            'delete' => ['method' => 'DELETE', 'stateless' => null],
        ]);
        //custom operations
        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'my_op' => ['method' => 'GET', 'controller' => 'some.service.name', 'requirements' => ['_format' => 'a valid format'], 'defaults' => ['my_default' => 'default_value', '_format' => 'a valid format'], 'condition' => "request.headers.get('User-Agent') matches '/firefox/i'", 'stateless' => null], //with controller
            'my_second_op' => ['method' => 'POST', 'options' => ['option' => 'option_value'], 'host' => '{subdomain}.api-platform.com', 'schemes' => ['https'], 'stateless' => null], //without controller, takes the default one
            'my_path_op' => ['method' => 'GET', 'path' => 'some/custom/path', 'stateless' => null], //custom path
            'my_stateless_op' => ['method' => 'GET', 'stateless' => true],
        ]);
        $resourceMetadata = $resourceMetadata->withSubresourceOperations([
            'subresources_get_subresource' => ['stateless' => true],
        ]);

        $routeCollection = $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);

        $this->assertEquals(
            $this->getRoute('/dummies/{id}.{_format}', 'api_platform.action.get_item', DummyEntity::class, 'get', ['GET'], false, ['id' => '\d+'], ['my_default' => 'default_value', '_stateless' => null]),
            $routeCollection->get('api_dummies_get_item')
        );

        $this->assertEquals(
            $this->getRoute('/dummies/{id}.{_format}', 'api_platform.action.delete_item', DummyEntity::class, 'delete', ['DELETE']),
            $routeCollection->get('api_dummies_delete_item')
        );

        $this->assertEquals(
            $this->getRoute('/dummies/{id}.{_format}', 'api_platform.action.put_item', DummyEntity::class, 'put', ['PUT']),
            $routeCollection->get('api_dummies_put_item')
        );

        $this->assertEquals(
            $this->getRoute('/dummies.{_format}', 'some.service.name', DummyEntity::class, 'my_op', ['GET'], true, ['_format' => 'a valid format'], ['my_default' => 'default_value', '_format' => 'a valid format', '_stateless' => null], [], '', [], "request.headers.get('User-Agent') matches '/firefox/i'"),
            $routeCollection->get('api_dummies_my_op_collection')
        );

        $this->assertEquals(
            $this->getRoute('/dummies.{_format}', 'api_platform.action.post_collection', DummyEntity::class, 'my_second_op', ['POST'], true, [], ['_stateless' => null], ['option' => 'option_value'], '{subdomain}.api-platform.com', ['https']),
            $routeCollection->get('api_dummies_my_second_op_collection')
        );

        $this->assertEquals(
            $this->getRoute('/some/custom/path', 'api_platform.action.get_collection', DummyEntity::class, 'my_path_op', ['GET'], true),
            $routeCollection->get('api_dummies_my_path_op_collection')
        );

        $this->assertEquals(
            $this->getRoute('/dummies.{_format}', 'api_platform.action.get_collection', DummyEntity::class, 'my_stateless_op', ['GET'], true, [], ['_stateless' => true]),
            $routeCollection->get('api_dummies_my_stateless_op_collection')
        );

        $this->assertEquals(
            $this->getSubresourceRoute('/dummies/{id}/subresources.{_format}', 'api_platform.action.get_subresource', RelatedDummyEntity::class, 'api_dummies_subresources_get_subresource', ['property' => 'subresource', 'identifiers' => ['id' => [DummyEntity::class, 'id', true]], 'collection' => true, 'operationId' => 'api_dummies_subresources_get_subresource'], [], ['_stateless' => true]),
            $routeCollection->get('api_dummies_subresources_get_subresource')
        );
    }

    public function testApiLoaderWithPrefix()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withShortName('dummy');
        $resourceMetadata = $resourceMetadata->withItemOperations([
            'get' => ['method' => 'GET', 'requirements' => ['id' => '\d+'], 'defaults' => ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'], 'stateless' => null],
            'put' => ['method' => 'PUT', 'stateless' => null],
            'delete' => ['method' => 'DELETE', 'stateless' => null],
        ]);
        $resourceMetadata = $resourceMetadata->withAttributes(['route_prefix' => '/foobar-prefix', 'identifiers' => 'id']);

        $routeCollection = $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);

        $this->assertEquals(
            $this->getRoute('/foobar-prefix/dummies/{id}.{_format}', 'api_platform.action.get_item', DummyEntity::class, 'get', ['GET'], false, ['id' => '\d+'], ['my_default' => 'default_value', '_stateless' => null]),
            $routeCollection->get('api_dummies_get_item')
        );

        $this->assertEquals(
            $this->getRoute('/foobar-prefix/dummies/{id}.{_format}', 'api_platform.action.delete_item', DummyEntity::class, 'delete', ['DELETE']),
            $routeCollection->get('api_dummies_delete_item')
        );

        $this->assertEquals(
            $this->getRoute('/foobar-prefix/dummies/{id}.{_format}', 'api_platform.action.put_item', DummyEntity::class, 'put', ['PUT']),
            $routeCollection->get('api_dummies_put_item')
        );
    }

    public function testNoMethodApiLoader()
    {
        $this->expectException(\RuntimeException::class);

        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withShortName('dummy');

        $resourceMetadata = $resourceMetadata->withItemOperations([
            'get' => [],
        ]);

        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'get' => ['method' => 'GET', 'stateless' => null],
        ]);

        $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);
    }

    public function testWrongMethodApiLoader()
    {
        $this->expectException(\RuntimeException::class);

        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withShortName('dummy');

        $resourceMetadata = $resourceMetadata->withItemOperations([
            'post' => ['method' => 'POST', 'stateless' => null],
        ]);

        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'get' => ['method' => 'GET', 'stateless' => null],
        ]);

        $this->getApiLoaderWithResourceMetadata($resourceMetadata)->load(null);
    }

    public function testNoShortNameApiLoader()
    {
        $this->expectException(InvalidResourceException::class);

        $this->getApiLoaderWithResourceMetadata(new ResourceMetadata())->load(null);
    }

    public function testRecursiveSubresource()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withShortName('dummy');
        $resourceMetadata = $resourceMetadata->withItemOperations([
            'get' => ['method' => 'GET', 'stateless' => null],
            'put' => ['method' => 'PUT', 'stateless' => null],
            'delete' => ['method' => 'DELETE', 'stateless' => null],
        ]);
        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'my_op' => ['method' => 'GET', 'controller' => 'some.service.name', 'stateless' => null], //with controller
            'my_second_op' => ['method' => 'POST', 'stateless' => null], //without controller, takes the default one
            'my_path_op' => ['method' => 'GET', 'path' => 'some/custom/path', 'stateless' => null], //custom path
        ]);

        $routeCollection = $this->getApiLoaderWithResourceMetadata($resourceMetadata, true)->load(null);

        $this->assertEquals(
            $this->getSubresourceRoute('/dummies/{id}/subresources.{_format}', 'api_platform.action.get_subresource', RelatedDummyEntity::class, 'api_dummies_subresources_get_subresource', ['property' => 'subresource', 'identifiers' => ['id' => [DummyEntity::class, 'id', true]], 'collection' => true, 'operationId' => 'api_dummies_subresources_get_subresource']),
            $routeCollection->get('api_dummies_subresources_get_subresource')
        );

        $this->assertEquals(
            $this->getSubresourceRoute('/related_dummies/{id}/recursivesubresource/subresources.{_format}', 'api_platform.action.get_subresource', RelatedDummyEntity::class, 'api_related_dummies_recursivesubresource_subresources_get_subresource', ['property' => 'subresource', 'identifiers' => ['id' => [RelatedDummyEntity::class, 'id', true], 'recursivesubresource' => [DummyEntity::class, 'id', false]], 'collection' => true, 'operationId' => 'api_related_dummies_recursivesubresource_subresources_get_subresource']),
            $routeCollection->get('api_related_dummies_recursivesubresource_subresources_get_subresource')
        );

        $this->assertEquals(
            $this->getSubresourceRoute('/related_dummies/{id}/recursivesubresource.{_format}', 'dummy_controller', DummyEntity::class, 'api_related_dummies_recursivesubresource_get_subresource', ['property' => 'recursivesubresource', 'identifiers' => ['id' => [RelatedDummyEntity::class, 'id', true]], 'collection' => false, 'operationId' => 'api_related_dummies_recursivesubresource_get_subresource']),
            $routeCollection->get('api_related_dummies_recursivesubresource_get_subresource')
        );

        $this->assertEquals(
            $this->getSubresourceRoute('/dummies/{id}/subresources/{subresource}/recursivesubresource.{_format}', 'api_platform.action.get_subresource', DummyEntity::class, 'api_dummies_subresources_recursivesubresource_get_subresource', ['property' => 'recursivesubresource', 'identifiers' => ['id' => [DummyEntity::class, 'id', true], 'subresource' => [RelatedDummyEntity::class, 'id', true]], 'collection' => false, 'operationId' => 'api_dummies_subresources_recursivesubresource_get_subresource']),
            $routeCollection->get('api_dummies_subresources_recursivesubresource_get_subresource')
        );

        $this->assertEquals(
            $this->getSubresourceRoute('/related_dummies/{id}/secondrecursivesubresource/subresources.{_format}', 'api_platform.action.get_subresource', RelatedDummyEntity::class, 'api_related_dummies_secondrecursivesubresource_subresources_get_subresource', ['property' => 'subresource', 'identifiers' => ['id' => [RelatedDummyEntity::class, 'id', true], 'secondrecursivesubresource' => [DummyEntity::class, 'id', false]], 'collection' => true, 'operationId' => 'api_related_dummies_secondrecursivesubresource_subresources_get_subresource']),
            $routeCollection->get('api_related_dummies_secondrecursivesubresource_subresources_get_subresource')
        );

        $this->assertEquals(
            $this->getSubresourceRoute('/related_dummies/{id}/secondrecursivesubresource.{_format}', 'api_platform.action.get_subresource', DummyEntity::class, 'api_related_dummies_secondrecursivesubresource_get_subresource', ['property' => 'secondrecursivesubresource', 'identifiers' => ['id' => [RelatedDummyEntity::class, 'id', true]], 'collection' => false, 'operationId' => 'api_related_dummies_secondrecursivesubresource_get_subresource']),
            $routeCollection->get('api_related_dummies_secondrecursivesubresource_get_subresource')
        );
    }

    private function getApiLoaderWithResourceMetadata(ResourceMetadata $resourceMetadata, $recursiveSubresource = false): ApiLoader
    {
        $routingConfig = __DIR__.'/../../../../src/Bridge/Symfony/Bundle/Resources/config/routing';

        $kernelProphecy = $this->prophesize(KernelInterface::class);
        $kernelProphecy->locateResource(Argument::any())->willReturn($routingConfig);
        $possibleArguments = [
            'api_platform.action.get_collection',
            'api_platform.action.post_collection',
            'api_platform.action.get_item',
            'api_platform.action.put_item',
            'api_platform.action.delete_item',
            'api_platform.action.get_subresource',
        ];
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        foreach ($possibleArguments as $possibleArgument) {
            $containerProphecy->has($possibleArgument)->willReturn(true);
        }

        $containerProphecy->has(Argument::type('string'))->willReturn(false);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceMetadata);

        $relatedDummyEntityMetadata = (new ResourceMetadata())->withShortName('related_dummies')->withSubresourceOperations([
            'recursivesubresource_get_subresource' => [
                'controller' => 'dummy_controller',
            ],
        ]);

        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->willReturn($relatedDummyEntityMetadata);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyEntity::class, RelatedDummyEntity::class]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->willReturn(new PropertyNameCollection(['id', 'subresource']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->willReturn(new PropertyNameCollection(['id', 'recursivesubresource', 'secondrecursivesubresource']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'id')->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'id')->willReturn(new PropertyMetadata());

        $relatedType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummyEntity::class);

        $subResourcePropertyMetadata = (new PropertyMetadata())
                                        ->withSubresource(new SubresourceMetadata(RelatedDummyEntity::class, true))
                                        ->withType(new Type(Type::BUILTIN_TYPE_ARRAY, false, \ArrayObject::class, true, null, $relatedType));

        if (false === $recursiveSubresource) {
            $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'recursivesubresource')->willReturn(new PropertyMetadata());
            $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'secondrecursivesubresource')->willReturn(new PropertyMetadata());
        } else {
            $dummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, DummyEntity::class);
            $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'recursivesubresource')
                ->willReturn((new PropertyMetadata())
                ->withSubresource(new SubresourceMetadata(DummyEntity::class, false))
                ->withType($dummyType));
            $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'secondrecursivesubresource')
                ->willReturn((new PropertyMetadata())
                ->withSubresource(new SubresourceMetadata(DummyEntity::class, false))
                ->withType($dummyType));
        }

        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'subresource')->willReturn($subResourcePropertyMetadata);

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);
        $identifiersExtractor = $identifiersExtractorProphecy->reveal();

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), new UnderscorePathSegmentNameGenerator(), $identifiersExtractor);

        return new ApiLoader($kernelProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactory, $operationPathResolver, $containerProphecy->reveal(), ['jsonld' => ['application/ld+json']], [], $subresourceOperationFactory, false, true, true, false, false, $identifiersExtractor);
    }

    private function getRoute(string $path, string $controller, string $resourceClass, string $operationName, array $methods, bool $collection = false, array $requirements = [], array $extraDefaults = ['_stateless' => null], array $options = [], string $host = '', array $schemes = [], string $condition = ''): Route
    {
        return new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => $extraDefaults['_format'] ?? null,
                '_api_resource_class' => $resourceClass,
                '_api_identifiers' => ['id'],
                '_api_has_composite_identifier' => false,
                sprintf('_api_%s_operation_name', $collection ? 'collection' : 'item') => $operationName,
            ] + $extraDefaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }

    private function getSubresourceRoute(string $path, string $controller, string $resourceClass, string $operationName, array $context, array $requirements = [], array $extraDefaults = ['_stateless' => null]): Route
    {
        return new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => $extraDefaults['_format'] ?? null,
                '_api_resource_class' => $resourceClass,
                '_api_subresource_operation_name' => $operationName,
                '_api_subresource_context' => $context,
                '_api_identifiers' => $context['identifiers'],
                '_api_has_composite_identifier' => false,
            ] + $extraDefaults,
            $requirements,
            [],
            '',
            [],
            ['GET']
        );
    }
}
