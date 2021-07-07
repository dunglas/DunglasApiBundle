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
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolver;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ApiLoaderTest extends TestCase
{
    use ProphecyTrait;

    public function testApiLoader()
    {
        $path = '/dummies/{id}.{_format}';

        $resourceCollection = new ResourceCollection([new Resource(
            shortName: 'dummy',
            operations: [
                // Default operations based on OperationResourceMetadataFactory
                'api_dummies_get_item' => new Get(
                    uriTemplate: $path,
                    defaults: ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'],
                    requirements: ['id' => '\d+'],
                    controller: 'api_platform.action.get_item',
                ),
                'api_dummies_put_item' => new Put(uriTemplate: $path),
                'api_dummies_delete_item' => new Delete(uriTemplate: $path),
                // Custom operations
                'api_dummies_my_op_collection' => new Get(
                    uriTemplate: $path,
                    defaults: ['my_default' => 'default_value', '_format' => 'a valid format'],
                    requirements: ['_format' => 'a valid format'],
                    condition: "request.headers.get('User-Agent') matches '/firefox/i'",
                    controller: 'some.service.name',
                    collection: true
                ), // With controller
                'api_dummies_my_second_op_collection' => new Post(
                    uriTemplate: $path,
                    options: ['option' => 'option_value'],
                    host: '{subdomain}.api-platform.com',
                    schemes: ['https'],
                ), //without controller, takes the default one
                'api_dummies_my_path_op_collection' => new Get(
                    uriTemplate: 'some/custom/path',
                    collection: true,
                ), // Custom path
                'api_dummies_my_stateless_op_collection' => new Get(
                    uriTemplate: $path,
                    stateless: true,
                    collection: true
                ),
            ],
            identifiers: ['id']
        )]);

        $routeCollection = $this->getApiLoaderWithResourceCollection($resourceCollection)->load(null);

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.get_item',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_get_item',
                $resourceCollection[0]->operations['api_dummies_get_item']->__serialize(),
                ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'],
                ['GET'],
                ['id' => '\d+']
            ),
            $routeCollection->get('api_dummies_get_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_delete_item',
                $resourceCollection[0]->operations['api_dummies_delete_item']->__serialize(),
                [],
                ['DELETE'],
                []
            ),
            $routeCollection->get('api_dummies_delete_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_put_item',
                $resourceCollection[0]->operations['api_dummies_put_item']->__serialize(),
                [],
                ['PUT'],
                []
            ),
            $routeCollection->get('api_dummies_put_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $path,
                'some.service.name',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_op_collection',
                $resourceCollection[0]->operations['api_dummies_my_op_collection']->__serialize(),
                ['my_default' => 'default_value', '_format' => 'a valid format'],
                ['GET'],
                ['_format' => 'a valid format'],
                [],
                '',
                [],
                "request.headers.get('User-Agent') matches '/firefox/i'",
            ),
            $routeCollection->get('api_dummies_my_op_collection')
        );

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_second_op_collection',
                $resourceCollection[0]->operations['api_dummies_my_second_op_collection']->__serialize(),
                [],
                ['POST'],
                [],
                ['option' => 'option_value'],
                '{subdomain}.api-platform.com',
                ['https'],
            ),
            $routeCollection->get('api_dummies_my_second_op_collection')
        );

        $this->assertEquals(
            $this->getRoute(
                'some/custom/path',
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_path_op_collection',
                $resourceCollection[0]->operations['api_dummies_my_path_op_collection']->__serialize(),
                [],
                ['GET'],
                [],
            ),
            $routeCollection->get('api_dummies_my_path_op_collection')
        );

        $this->assertEquals(
            $this->getRoute(
                $path,
                'api_platform.action.placeholder',
                true,
                RelatedDummyEntity::class,
                [],
                'api_dummies_my_stateless_op_collection',
                $resourceCollection[0]->operations['api_dummies_my_stateless_op_collection']->__serialize(),
                [],
                ['GET'],
                [],
            ),
            $routeCollection->get('api_dummies_my_stateless_op_collection')
        );
    }

    public function testApiLoaderWithPrefix()
    {
        $prefix = '/foobar-prefix';
        $path = '/dummies/{id}.{_format}';

        $resourceCollection = new ResourceCollection([new Resource(
            shortName: 'dummy',
            operations: [
                'api_dummies_get_item' => new Get(
                    uriTemplate: $path,
                    routePrefix: $prefix,
                    defaults: ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'],
                    requirements: ['id' => '\d+'],
                ),
                'api_dummies_put_item' => new Put(uriTemplate: $path, routePrefix: $prefix),
                'api_dummies_delete_item' => new Delete(uriTemplate: $path, routePrefix: $prefix),
            ],
            identifiers: ['id'],
        )]);

        $routeCollection = $this->getApiLoaderWithResourceCollection($resourceCollection)->load(null);

        $prefixedPath = $prefix.$path;

        $this->assertEquals(
            $this->getRoute(
                $prefixedPath,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_get_item',
                $resourceCollection[0]->operations['api_dummies_get_item']->__serialize(),
                ['my_default' => 'default_value', '_controller' => 'should_not_be_overriden'],
                ['GET'],
                ['id' => '\d+']
            ),
            $routeCollection->get('api_dummies_get_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $prefixedPath,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_delete_item',
                $resourceCollection[0]->operations['api_dummies_delete_item']->__serialize(),
                [],
                ['DELETE'],
            ),
            $routeCollection->get('api_dummies_delete_item')
        );

        $this->assertEquals(
            $this->getRoute(
                $prefixedPath,
                'api_platform.action.placeholder',
                null,
                RelatedDummyEntity::class,
                [],
                'api_dummies_put_item',
                $resourceCollection[0]->operations['api_dummies_put_item']->__serialize(),
                [],
                ['PUT'],
            ),
            $routeCollection->get('api_dummies_put_item')
        );
    }

    private function getApiLoaderWithResourceCollection(ResourceCollection $resourceCollection): ApiLoader
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
        ];
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        foreach ($possibleArguments as $possibleArgument) {
            $containerProphecy->has($possibleArgument)->willReturn(true);
        }

        $containerProphecy->has(Argument::type('string'))->willReturn(false);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceCollection);
        $resourceMetadataFactoryProphecy->create(RelatedDummyEntity::class)->willReturn($resourceCollection);

        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyEntity::class, RelatedDummyEntity::class]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(DummyEntity::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummyEntity::class)->willReturn(new PropertyNameCollection(['id']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(RelatedDummyEntity::class, 'id')->willReturn(new PropertyMetadata());
        $propertyMetadataFactoryProphecy->create(DummyEntity::class, 'id')->willReturn(new PropertyMetadata());

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);
        $identifiersExtractor = $identifiersExtractorProphecy->reveal();

        return new ApiLoader($kernelProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactory, $operationPathResolver, $containerProphecy->reveal(), ['jsonld' => ['application/ld+json']], [], null, false, true, true, false, false, $identifiersExtractor);
    }

    private function getRoute(string $path, string $controller, ?bool $stateless, string $resourceClass, array $identifiers, string $operationName, array $serializedOperation, array $extraDefaults = [], array $methods = [], array $requirements = [], array $options = [], string $host = '', array $schemes = [], string $condition = ''): Route
    {
        return new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => null,
                '_stateless' => $stateless,
                '_api_resource_class' => $resourceClass,
                '_api_identifiers' => $identifiers,
                '_api_has_composite_identifier' => null,
                '_api_operation_name' => $operationName,
                '_api_operation' => $serializedOperation,
            ] + $extraDefaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );
    }
}
