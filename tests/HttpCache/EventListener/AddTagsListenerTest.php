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

namespace ApiPlatform\Core\Tests\HttpCache\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\HttpCache\EventListener\AddTagsListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddTagsListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testDoNotSetHeaderWhenMethodNotCacheable()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $request->setMethod('PUT');

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testDoNotSetHeaderWhenResponseNotCacheable()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response();
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testDoNotSetHeaderWhenNotAnApiOperation()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo', '/bar']]),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testDoNotSetHeaderWhenEmptyTagList()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => [], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testDoNotAddTagsWhenCacheInvalidationFlagIsFalse()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_cache_tags_invalidation' => false]);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testDoNotAddTagsWhenDisabledInOperationAttribute()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceMetadata = new ResourceMetadata('Dummy', null, null, [
            'get' => [
                'cache_tags_invalidation' => false,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertFalse($response->headers->has('Cache-Tags'));
    }

    public function testAddTags()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/foo,/bar', $response->headers->get('Cache-Tags'));
    }

    public function testAddCollectionIri()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/foo,/bar,/dummies', $response->headers->get('Cache-Tags'));
    }

    public function testAddCollectionIriWhenCollectionIsEmpty()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => [], '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/dummies', $response->headers->get('Cache-Tags'));
    }

    public function testAddSubResourceCollectionIri()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_subresource_operation_name' => 'api_dummies_relatedDummies_get_subresource', '_api_subresource_context' => ['collection' => true]]),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/foo,/bar,/dummies', $response->headers->get('Cache-Tags'));
    }
}
