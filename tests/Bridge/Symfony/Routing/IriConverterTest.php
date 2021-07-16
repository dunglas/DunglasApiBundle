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
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class IriConverterTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

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
            '_api_identifiers' => ['id'],
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
            ->getItem(Dummy::class, ['id' => 3], 'get', [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])
            ->shouldBeCalled()->willReturn(null);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_item_operation_name' => 'get',
            '_api_identifiers' => ['id'],
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy, null, $itemDataProviderProphecy);
        $converter->getItemFromIri('/users/3');
    }

    public function testGetItemFromIri()
    {
        $item = new \stdClass();
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(Dummy::class, ['id' => 3], 'get', ['fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->shouldBeCalled()->willReturn($item);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_item_operation_name' => 'get',
            '_api_identifiers' => ['id'],
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy, null, $itemDataProviderProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3', ['fetch_data' => true]), $item);
    }

    public function testGetItemFromIriWithOperationName()
    {
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', ['id' => 3], 'operation_name', ['fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])
            ->willReturn('foo')
            ->shouldBeCalledTimes(1);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'operation_name',
            '_api_identifiers' => ['id'],
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

    public function testGetIriFromResourceClassAbsoluteUrl()
    {
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', [], UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('', '', '', [], [], ['url_generation_strategy' => UrlGeneratorInterface::ABS_URL]));

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, null, $resourceMetadataFactoryProphecy->reveal());
        $this->assertEquals($converter->getIriFromResourceClass(Dummy::class), 'http://example.com/dummies');
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

    /**
     * @group legacy
     */
    public function testGetSubresourceIriFromResourceClass()
    {
        $this->expectDeprecation('getSubresourceIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0');
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::SUBRESOURCE, Argument::type('array'))->willReturn('api_dummies_related_dummies_get_subresource');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_dummies_related_dummies_get_subresource', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies/1/related_dummies');

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $this->assertEquals($converter->getSubresourceIriFromResourceClass(Dummy::class, ['subresource_identifiers' => ['id' => 1], 'subresource_resources' => [RelatedDummy::class => 1]]), '/dummies/1/related_dummies');
    }

    /**
     * @group legacy
     */
    public function testNotAbleToGenerateGetSubresourceIriFromResourceClass()
    {
        $this->expectDeprecation('getSubresourceIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to generate an IRI for "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy"');

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::SUBRESOURCE, Argument::type('array'))->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy);
        $converter->getSubresourceIriFromResourceClass(Dummy::class, ['subresource_identifiers' => ['id' => 1], 'subresource_resources' => [RelatedDummy::class => 1]]);
    }

    /**
     * @group legacy
     */
    public function testGetItemIriFromResourceClass()
    {
        $this->expectDeprecation('getItemIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0');
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::ITEM)->willReturn('api_dummies_get_item');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_dummies_get_item', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies/1');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['composite_identifier' => true]));

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, null, $resourceMetadataFactoryProphecy->reveal());
        $this->assertEquals($converter->getItemIriFromResourceClass(Dummy::class, ['id' => 1]), '/dummies/1');
    }

    /**
     * @group legacy
     */
    public function testGetItemIriFromResourceClassAbsoluteUrl()
    {
        $this->expectDeprecation('getItemIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0');
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::ITEM)->willReturn('api_dummies_get_item');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_dummies_get_item', ['id' => 1], UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/1');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('', '', '', [], [], ['url_generation_strategy' => UrlGeneratorInterface::ABS_URL]));

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, null, $resourceMetadataFactoryProphecy->reveal());
        $this->assertEquals($converter->getItemIriFromResourceClass(Dummy::class, ['id' => 1]), 'http://example.com/dummies/1');
    }

    /**
     * @group legacy
     */
    public function testNotAbleToGenerateGetItemIriFromResourceClass()
    {
        $this->expectDeprecation('getItemIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to generate an IRI for "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy"');

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::ITEM)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['composite_identifier' => true]));

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, null, $resourceMetadataFactoryProphecy->reveal());
        $converter->getItemIriFromResourceClass(Dummy::class, ['id' => 1]);
    }

    public function testGetItemFromIriWithIdentifierConverter()
    {
        $item = new \stdClass();
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem(Dummy::class, ['id' => 3], 'get', ['fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->shouldBeCalled()->willReturn($item);
        $identifierConverterProphecy = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverterProphecy->convert(['id' => '3'], Dummy::class)->shouldBeCalled()->willReturn(['id' => 3]);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_item_operation_name' => 'get',
            '_api_identifiers' => ['id' => [Dummy::class, 'id']],
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $converter = $this->getIriConverter($routerProphecy, null, $itemDataProviderProphecy, null, $identifierConverterProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3', ['fetch_data' => true]), $item);
    }

    public function testGetItemFromIriWithSubresourceDataProvider()
    {
        $item = new \stdClass();
        $subresourceContext = ['identifiers' => ['id' => [Dummy::class, 'id', true]]];
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3/adresses')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_subresource_context' => $subresourceContext,
            '_api_subresource_operation_name' => 'get_subresource',
            '_api_identifiers' => $subresourceContext['identifiers'],
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProviderProphecy->getSubresource(Dummy::class, ['id' => ['id' => 3]], $subresourceContext + ['fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true], 'get_subresource')->shouldBeCalled()->willReturn($item);
        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, $subresourceDataProviderProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3/adresses', ['fetch_data' => true]), $item);
    }

    public function testGetItemFromIriWithSubresourceDataProviderNotFound()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Item not found for "/users/3/adresses".');

        $subresourceContext = ['identifiers' => ['id' => [Dummy::class, 'id', true]]];
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3/adresses')->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_subresource_context' => $subresourceContext,
            '_api_subresource_operation_name' => 'get_subresource',
            '_api_identifiers' => $subresourceContext['identifiers'],
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $identifierConverterProphecy = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverterProphecy->convert(['id' => '3'], Dummy::class)->shouldBeCalled()->willReturn(['id' => 3]);
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
            '_api_identifiers' => ['id'],
            'id' => 3,
        ])->shouldBeCalledTimes(1);
        $identifierConverterProphecy = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverterProphecy->convert(['id' => '3'], Dummy::class)->shouldBeCalled()->willThrow(new InvalidIdentifierException('Item not found for "/users/3".'));
        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, $identifierConverterProphecy);
        $this->assertEquals($converter->getItemFromIri('/users/3', ['fetch_data' => true]), $item);
    }

    public function testNoIdentifiersException()
    {
        $this->markTestSkipped('The method "generateIdentifiersUrl" has been removed.');
        /* @phpstan-ignore-next-line */
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
     */
    public function testLegacyConstructor()
    {
        $this->expectDeprecation('Not injecting ApiPlatform\Core\Api\ResourceClassResolverInterface in the IdentifiersExtractor might introduce cache issues with object identifiers.');
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

    /**
     * @requires PHP 8.0
     */
    public function testGetIriFromResourceClassWithResourceCollection()
    {
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies')->shouldNotBeCalled();

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('operationName', [], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies');
        $resourceCollectionMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resource = new Resource();
        $itemOperation = new Get();
        $itemOperation->identifiers = ['id'];
        $resource->operations = ['itemOperationName' => $itemOperation, 'operationName' => new Get()];
        $resourceCollectionMetadataFactory->create(Dummy::class)->willReturn(new ResourceCollection([$resource]));

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, null, $resourceCollectionMetadataFactory->reveal());
        $this->assertEquals($converter->getIriFromResourceClass(Dummy::class, UrlGeneratorInterface::ABS_PATH, ['operation_name' => 'operationName']), '/dummies');
    }

    /**
     * @requires PHP 8.0
     */
    public function testGetIriFromResourceClassWithResourceCollectionNotFound()
    {
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', [], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies');
        $resourceCollectionMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceCollectionMetadataFactory->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, null, $resourceCollectionMetadataFactory->reveal());
        $this->assertEquals($converter->getIriFromResourceClass(Dummy::class, UrlGeneratorInterface::ABS_PATH, ['operation_name' => null]), '/dummies');
    }

    /**
     * @requires PHP 8.0
     */
    public function testGetIriFromItem()
    {
        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies')->shouldNotBeCalled();

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('itemOperationName', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies/1');
        $resourceCollectionMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resource = new Resource();
        $itemOperation = new Get();
        $itemOperation->identifiers = ['id'];
        $resource->operations = ['itemOperationName' => $itemOperation, 'operationName' => new Get()];
        $resourceCollectionMetadataFactory->create(Dummy::class)->willReturn(new ResourceCollection([$resource]));

        $converter = $this->getIriConverter($routerProphecy, $routeNameResolverProphecy, null, null, null, $resourceCollectionMetadataFactory->reveal());
        $dummy = new Dummy();
        $dummy->setId(1);
        $this->assertEquals($converter->getIriFromItem($dummy, UrlGeneratorInterface::ABS_PATH, ['identifiers' => ['id' => [Dummy::class, 'id']], 'operation_name' => 'itemOperationName']), '/dummies/1');
    }

    private function getResourceClassResolver()
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(function ($args) {
            return true;
        });

        $resourceClassResolver->getResourceClass(Argument::cetera())->will(function ($args) {
            return \get_class($args[0]);
        });

        return $resourceClassResolver->reveal();
    }

    private function getIriConverter($routerProphecy = null, $routeNameResolverProphecy = null, $itemDataProviderProphecy = null, $subresourceDataProviderProphecy = null, $identifierConverterProphecy = null, $resourceMetadataFactory = null)
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

        if (null === $identifierConverterProphecy) {
            $identifierConverterProphecy = $this->prophesize(IdentifierConverterInterface::class);
            $identifierConverterProphecy->convert(Argument::type('array'), Argument::type('string'))->will(function ($args) {
                return $args[0];
            });
        }

        return new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProvider->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $subresourceDataProviderProphecy ? $subresourceDataProviderProphecy->reveal() : null,
            $identifierConverterProphecy->reveal(),
            null,
            $resourceMetadataFactory
        );
    }
}
