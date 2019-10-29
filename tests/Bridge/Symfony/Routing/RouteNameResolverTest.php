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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class RouteNameResolverTest extends TestCase
{
    public function testConstruct()
    {
        $routerProphecy = $this->prophesize(RouterInterface::class);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());

        $this->assertInstanceOf(RouteNameResolverInterface::class, $routeNameResolver);
    }

    public function testGetRouteNameForItemRouteWithNoMatchingRoute()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No item route associated with the type "AppBundle\\Entity\\User".');

        $routeCollection = new RouteCollection();
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $routeNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM);
    }

    /**
     * @group legacy
     * @expectedDeprecation Using a boolean for the Operation Type is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testGetRouteNameForItemRouteLegacy()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', false);

        $this->assertSame('some_item_route', $actual);
    }

    public function testGetRouteNameForItemRoute()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM);

        $this->assertSame('some_item_route', $actual);
    }

    public function testGetRouteNameForItemRouteWithParentResource()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\UserDetails',
            '_api_parent_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM);

        $this->assertSame('some_item_route', $actual);
    }

    public function testGetRouteNameForCollectionRouteWithNoMatchingRoute()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No collection route associated with the type "AppBundle\\Entity\\User".');

        $routeCollection = new RouteCollection();
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $routeNameResolver->getRouteName('AppBundle\Entity\User', OperationType::COLLECTION);
    }

    /**
     * @group legacy
     * @expectedDeprecation Using a boolean for the Operation Type is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testGetRouteNameForCollectionRouteLegacy()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', true);

        $this->assertSame('some_collection_route', $actual);
    }

    public function testGetRouteNameForCollectionRoute()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', OperationType::COLLECTION);

        $this->assertSame('some_collection_route', $actual);
    }

    public function testGetRouteNameForSubresourceRoute()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('a_some_subresource_route', new Route('/a/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_subresource_operation_name' => 'some_other_item_op',
            '_api_subresource_context' => ['identifiers' => [[1, 'bar']]],
        ]));
        $routeCollection->add('b_some_subresource_route', new Route('/b/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_subresource_operation_name' => 'some_item_op',
            '_api_subresource_context' => ['identifiers' => [[1, 'foo']]],
        ]));
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', OperationType::SUBRESOURCE, ['subresource_resources' => ['foo' => 1]]);

        $this->assertSame('b_some_subresource_route', $actual);
    }
}
