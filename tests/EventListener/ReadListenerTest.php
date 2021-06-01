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

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\EventListener\ReadListener;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ReadListenerTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    public function testNotAnApiPlatformRequest()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn(new Request())->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    /**
     * @group legacy
     */
    public function testLegacyConstructor()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn(new Request())->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testDoNotReadWhenReceiveFlagIsFalse()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection(Argument::cetera())->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem(Argument::cetera())->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource(Argument::cetera())->shouldNotBeCalled();

        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $request = new Request([], [], ['id' => 1, 'data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'put', '_api_receive' => false]);
        $request->setMethod('PUT');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testDoNotReadWhenDisabledInOperationAttribute()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection(Argument::cetera())->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem(Argument::cetera())->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource(Argument::cetera())->shouldNotBeCalled();

        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $resourceMetadata = new ResourceMetadata('Dummy', null, null, [
            'put' => [
                'read' => false,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $request = new Request([], [], ['id' => 1, 'data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'put']);
        $request->setMethod('PUT');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveCollectionPost()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], [], '{}');
        $request->setMethod('POST');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertFalse($request->attributes->has('data'));
        $this->assertFalse($request->attributes->has('previous_data'));
    }

    public function testRetrieveCollectionGet()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection('Foo', 'get', ['filters' => ['foo' => 'bar'], IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->willReturn([])->shouldBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], ['QUERY_STRING' => 'foo=bar']);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame([], $request->attributes->get('data'));
        $this->assertFalse($request->attributes->has('previous_data'));
    }

    public function testRetrieveItem()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '1'], 'Foo')->shouldBeCalled()->willReturn(['id' => '1']);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $data = new \stdClass();
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', ['id' => '1'], 'get', [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->willReturn($data)->shouldBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => '1', '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame($data, $request->attributes->get('data'));
        $this->assertEquals($data, $request->attributes->get('previous_data'));
    }

    public function testRetrieveItemNoIdentifier()
    {
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());

        $request->attributes->get('data');
    }

    public function testRetrieveSubresource()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '1'], 'Foo')->shouldBeCalled()->willReturn(['id' => '1']);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $data = [new \stdClass()];
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource('Foo', ['id' => ['id' => '1']], ['identifiers' => [['id', 'Bar', true, ['id']]], 'property' => 'bar', IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true], 'get')->willReturn($data)->shouldBeCalled();

        $request = new Request([], [], ['id' => '1', '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true, ['id']]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame($data, $request->attributes->get('data'));
        $this->assertSame($data, $request->attributes->get('previous_data'));
    }

    public function testRetrieveSubresourceNoDataProvider()
    {
        $this->expectException(RuntimeException::class);

        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '1'], 'Foo')->shouldBeCalled()->willReturn(['id' => '1']);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => '1', '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true, ['id']]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), null, null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());

        $request->attributes->get('data');
    }

    public function testRetrieveSubresourceNotFound()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '1'], 'Foo')->willThrow(new InvalidIdentifierException())->shouldBeCalled();
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => '1', '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true, ['id']]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $this->prophesize(SubresourceDataProviderInterface::class)->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveItemNotFound()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '22'], 'Foo')->shouldBeCalled()->willReturn(['id' => 22]);
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', ['id' => 22], 'get', [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->willReturn(null)->shouldBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => '22', '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveBadItemNormalizedIdentifiers()
    {
        $this->expectException(NotFoundHttpException::class);

        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '1'], 'Foo')->shouldBeCalled()->willThrow(new InvalidIdentifierException());

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => '1', '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod(Request::METHOD_GET);

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveBadSubresourceNormalizedIdentifiers()
    {
        $this->expectException(NotFoundHttpException::class);

        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(Argument::type('array'), Argument::type('string'))->shouldBeCalled()->willThrow(new InvalidIdentifierException());

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => '1', '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true, ['id']]], 'property' => 'bar']]);
        $request->setMethod(Request::METHOD_GET);

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveItemWithCompositeIdentifiersCallsIdentifierConverter()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['foo' => '22', 'bar' => 'test'], 'DummyWithCompositeIdentifier')->shouldBeCalled()->willReturn(['foo' => 22, 'bar' => 'test']);
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('DummyWithCompositeIdentifier', ['foo' => 22, 'bar' => 'test'], 'get', [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->willReturn(null)->shouldBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => 'foo=22;bar=test', '_api_resource_class' => 'DummyWithCompositeIdentifier', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_identifiers' => ['foo', 'bar'], '_api_has_composite_identifier' => true]);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveOperationWithStateProvider()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '22'], 'Foo')->shouldBeCalled()->willReturn(['id' => 22]);
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $stateProvider = $this->prophesize(ProviderInterface::class);
        $stateProvider->provide('Foo', ['id' => 22], Argument::cetera())->shouldBeCalled()->willReturn(null);

        $request = new Request([], [], ['id' => '22', '_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_identifiers' => ['id' => ['Foo', 'id']]]);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal(), null, $stateProvider->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    /**
     * @group legacy
     */
    public function testRetrieveOperationWithoutStateProvider()
    {
        $this->expectDeprecation('Using a #[Resource] without a state provider is deprecated since 2.7 and will not be possible anymore in 3.0.');
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(['id' => '22'], 'Foo')->shouldBeCalled()->willReturn(['id' => 22]);
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', ['id' => 22], 'get', Argument::cetera())->willReturn(null)->shouldBeCalled();
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => '22', '_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_identifiers' => ['id' => ['Foo', 'id']]]);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }
}
