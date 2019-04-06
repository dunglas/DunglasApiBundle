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

use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class IriConverterTest extends TestCase
{
    public function testGetItemFromIriNoRouteException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No route matches "/users/3".');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willThrow(new RouteNotFoundException())->shouldBeCalledTimes(1);
        $converter = $this->getIriConverter($routerProphecy);
        $converter->getItemFromIri('/users/3');
    }

    public function testGetItemFromIriNoResourceException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No resource associated to "/users/3".');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy);
        $converter->getItemFromIri('/users/3');
    }

    public function testGetItemFromIriCollectionRouteException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The iri "/users" references a collection not an item.');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_collection_operation_name' => 'get',
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy);
        $converter->getItemFromIri('/users');
    }

    public function testGetItemFromIriItemNotFoundException()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Item not found for "/users/3".');

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy
            ->getItem(Dummy::class, 3, 'get', [])
            ->shouldBeCalled()->willReturn(null);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_item_operation_name' => 'get',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy, null, $itemDataProviderProphecy);
        $converter->getItemFromIri('/users/3');
    }

    public function testGetItemFromIri()
    {
        $item = new \stdClass();
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(Dummy::class, 3, 'get', ['fetch_data' => true])->shouldBeCalled()->willReturn($item);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_item_operation_name' => 'get',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy, null, $itemDataProviderProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3', ['fetch_data' => true]), $item);
    }

    public function testGetItemFromIriWithOperationName()
    {
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', '3', 'operation_name', ['fetch_data' => true])
            ->willReturn('foo')
            ->shouldBeCalledTimes(1);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'operation_name',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy, null, $itemDataProviderProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3', ['fetch_data' => true]), 'foo');
    }

    public function testGetIriFromResourceClass()
    {
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', [], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies');

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $this->assertEquals($converter->getIriFromResourceClass(Dummy::class), '/dummies');
    }

    public function testNotAbleToGenerateGetIriFromResourceClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to generate an IRI for "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy"');

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', [], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $converter->getIriFromResourceClass(Dummy::class);
    }

    public function testGetSubresourceIriFromResourceClass()
    {
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::SUBRESOURCE, Argument::type('array'))->willReturn('api_dummies_related_dummies_get_subresource');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_dummies_related_dummies_get_subresource', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies/1/related_dummies');

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $this->assertEquals($converter->getSubresourceIriFromResourceClass(Dummy::class, ['subresource_identifiers' => ['id' => 1], 'subresource_resources' => [RelatedDummy::class => 1]]), '/dummies/1/related_dummies');
    }

    public function testNotAbleToGenerateGetSubresourceIriFromResourceClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to generate an IRI for "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy"');

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::SUBRESOURCE, Argument::type('array'))->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $converter->getSubresourceIriFromResourceClass(Dummy::class, ['subresource_identifiers' => ['id' => 1], 'subresource_resources' => [RelatedDummy::class => 1]]);
    }

    public function testGetItemIriFromResourceClass()
    {
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::ITEM)->willReturn('api_dummies_get_item');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_dummies_get_item', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies/1');

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $this->assertEquals($converter->getItemIriFromResourceClass(Dummy::class, ['id' => 1]), '/dummies/1');
    }

    public function testNotAbleToGenerateGetItemIriFromResourceClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to generate an IRI for "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy"');

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::ITEM)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $converter->getItemIriFromResourceClass(Dummy::class, ['id' => 1]);
    }

    public function testGetItemFromIriWithIdentifierConverter()
    {
        $item = new \stdClass();
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(Dummy::class, ['id' => 3], 'get', ['fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->shouldBeCalled()->willReturn($item);
        $identifierConverterProphecy = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverterProphecy->convert('3', Dummy::class)->shouldBeCalled()->willReturn(['id' => 3]);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_item_operation_name' => 'get',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy, null, $itemDataProviderProphecy, null, $identifierConverterProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3', ['fetch_data' => true]), $item);
    }

    public function testGetItemFromIriWithSubresourceDataProvider()
    {
        $item = new \stdClass();
        $subresourceContext = ['identifiers' => [['id', Dummy::class, true, 'id']]];
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3/adresses')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_subresource_context' => $subresourceContext,
            '_api_subresource_operation_name' => 'get_subresource',
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProviderProphecy->getSubresource(Dummy::class, ['id' => 3], $subresourceContext + ['fetch_data' => true], 'get_subresource')->shouldBeCalled()->willReturn($item);
        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, $subresourceDataProviderProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3/adresses', ['fetch_data' => true]), $item);
    }

    public function testGetItemFromIriWithSubresourceDataProviderNotFound()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Item not found for "/users/3/adresses".');

        $subresourceContext = ['identifiers' => [['id', Dummy::class, true, 'id']]];
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3/adresses')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_subresource_context' => $subresourceContext,
            '_api_subresource_operation_name' => 'get_subresource',
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $identifierConverterProphecy = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverterProphecy->convert('3', Dummy::class)->shouldBeCalled()->willReturn(['id' => 3]);
        $subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProviderProphecy->getSubresource(Dummy::class, ['id' => ['id' => 3]], $subresourceContext + ['fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true], 'get_subresource')->shouldBeCalled()->willReturn(null);
        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, $subresourceDataProviderProphecy, $identifierConverterProphecy);
        $converter->getItemFromIri('/users/3/adresses', ['fetch_data' => true]);
    }

    public function testGetItemFromIriBadIdentifierException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item not found for "/users/3".');

        $item = new \stdClass();
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_item_operation_name' => 'get_subresource',
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $identifierConverterProphecy = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverterProphecy->convert('3', Dummy::class)->shouldBeCalled()->willThrow(new InvalidIdentifierException('Item not found for "/users/3".'));
        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, $identifierConverterProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3', ['fetch_data' => true]), $item);
    }

    public function testNoIdentifiersException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No identifiers defined for resource of type "\App\Entity\Sample"');

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);

        $method = new \ReflectionMethod(IriConverter::class, 'generateIdentifiersUrl');
        $method->setAccessible(true);
        $method->invoke($converter, [], '\App\Entity\Sample');
    }

    /**
     * @group legacy
     * @expectedDeprecation Not injecting "ApiPlatform\Core\Api\IdentifiersExtractorInterface" is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     * @expectedDeprecation Not injecting ApiPlatform\Core\Api\ResourceClassResolverInterface in the CachedIdentifiersExtractor might introduce cache issues with object identifiers.
     */
    public function testLegacyConstructor()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        new IriConverter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null
        );
    }

    private function getResourceClassResolver()
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(function ($args) {
            return true;
        });

        return $resourceClassResolver->reveal();
    }

    private function getIriConverter($routerProphecy = null, $routeNameResolverProphecy = null, $itemDataProviderProphecy = null, $subresourceDataProviderProphecy = null, $identifierConverterProphecy = null)
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        if (!$routerProphecy) {
            $routerProphecy = $this->prophesize(RouterInterface::class);
        }

        if (!$routeNameResolverProphecy) {
            $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        }

        $itemDataProvider = $itemDataProviderProphecy ?: $this->prophesize(ItemDataProviderInterface::class);

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        return new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProvider->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $subresourceDataProviderProphecy ? $subresourceDataProviderProphecy->reveal() : null,
            $identifierConverterProphecy ? $identifierConverterProphecy->reveal() : null
        );
    }
}
