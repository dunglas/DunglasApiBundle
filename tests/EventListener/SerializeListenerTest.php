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

use ApiPlatform\Core\EventListener\SerializeListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\ResourceList;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializeListenerTest extends TestCase
{
    public function testDoNotSerializeWhenControllerResultIsResponse()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $request = new Request();

        $eventProphecy = $this->prophesize(ViewEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new Response());
        $eventProphecy->getRequest()->willReturn($request);

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create(Argument::cetera());

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenRespondFlagIsFalse()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $dummy = new Dummy();

        $request = new Request([], [], ['data' => $dummy, '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post', '_api_respond' => false]);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(ViewEvent::class);
        $eventProphecy->getControllerResult()->willReturn($dummy);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->setControllerResult(Argument::any())->shouldNotBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenDisabledInOperationAttribute()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $resourceMetadata = new ResourceMetadata('Dummy', null, null, [], [
            'post' => [
                'serialize' => false,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $dummy = new Dummy();

        $request = new Request([], [], ['data' => $dummy, '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(ViewEvent::class);
        $eventProphecy->getControllerResult()->willReturn($dummy);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->setControllerResult(Argument::any())->shouldNotBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    /**
     * @group legacy
     */
    public function testLegacySerializeCollectionOperation(): void
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'collection_operation_name' => 'get'];

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $this->doTestSerializeCollectionOperation($serializerContextBuilderProphecy->reveal());
    }

    public function testSerializeCollectionOperation(): void
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'collection_operation_name' => 'get'];

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'get', true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $this->doTestSerializeCollectionOperation($serializerContextFactoryProphecy->reveal());
    }

    private function doTestSerializeCollectionOperation($serializerContextFactory): void
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $serializerProphecy
            ->serialize(
                Argument::any(),
                'xml',
                Argument::allOf(
                    Argument::that(function (array $context) {
                        return $context['resources'] instanceof ResourceList && $context['resources_to_push'] instanceof ResourceList;
                    }),
                    Argument::withEntry('request_uri', ''),
                    Argument::withEntry('resource_class', 'Foo'),
                    Argument::withEntry('collection_operation_name', 'get')
                )
            )
            ->willReturn('bar')
            ->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(ViewEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextFactory);
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testSerializeCollectionOperationWithOutputClassDisabled()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'collection_operation_name' => 'post', 'output' => ['class' => null]];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_api_output_class' => false]);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(ViewEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->setControllerResult(null)->shouldBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'get', true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testSerializeItemOperation()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'item_operation_name' => 'get'];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy
            ->serialize(
                Argument::any(),
                'xml',
                Argument::allOf(
                    Argument::that(function (array $context) {
                        return $context['resources'] instanceof ResourceList && $context['resources_to_push'] instanceof ResourceList;
                    }),
                    Argument::withEntry('request_uri', ''),
                    Argument::withEntry('resource_class', 'Foo'),
                    Argument::withEntry('item_operation_name', 'get')
                )
            )
            ->willReturn('bar')
            ->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(ViewEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'get', true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testEncode()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(EncoderInterface::class);
        $serializerProphecy->encode(Argument::any(), 'xml')->willReturn('bar')->shouldBeCalled();
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(ViewEvent::class);
        $eventProphecy->getControllerResult()->willReturn([])->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }
}
