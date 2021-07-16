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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\EventListener\WriteListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConcreteDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class WriteListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testOnKernelViewWithControllerResultAndPersist()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->willReturn($dummy)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/dummy/1')->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $request->setMethod($httpMethod);
            $request->attributes->set(sprintf('_api_%s_operation_name', 'POST' === $httpMethod ? 'collection' : 'item'), strtolower($httpMethod));

            (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->onKernelView($event);
            $this->assertSame($dummy, $event->getControllerResult());
            $this->assertEquals('/dummy/1', $request->attributes->get('_api_write_item_iri'));
        }
    }

    /**
     * @group legacy
     * @expectedDeprecation Not returning an object from ApiPlatform\Core\DataPersister\DataPersisterInterface::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3.
     */
    public function testOnKernelViewWithControllerResultAndPersistReturningVoid()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $request->setMethod($httpMethod);
            $request->attributes->set(sprintf('_api_%s_operation_name', 'POST' === $httpMethod ? 'collection' : 'item'), strtolower($httpMethod));

            (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
            $this->assertSame($dummy, $event->getControllerResult());
        }
    }

    /**
     * @see https://github.com/api-platform/core/issues/1799
     * @see https://github.com/api-platform/core/issues/2692
     */
    public function testOnKernelViewWithControllerResultAndPersistWithImmutableResource()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dummy2 = new Dummy();
        $dummy2->setId(2);
        $dummy2->setName('Dummyferoce');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true);
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->willReturn($dummy2);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy2, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/dummy/2')->shouldBeCalled();

        $writeListener = new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal());

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $event = new ViewEvent(
                $this->prophesize(HttpKernelInterface::class)->reveal(),
                $request,
                HttpKernelInterface::MASTER_REQUEST,
                $dummy
            );

            $request->setMethod($httpMethod);
            $request->attributes->set(sprintf('_api_%s_operation_name', 'POST' === $httpMethod ? 'collection' : 'item'), strtolower($httpMethod));

            $writeListener->onKernelView($event);

            $this->assertSame($dummy2, $event->getControllerResult());
            $this->assertEquals('/dummy/2', $request->attributes->get('_api_write_item_iri'));
        }
    }

    public function testOnKernelViewDoNotCallIriConverterWhenOutputClassDisabled()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['output' => ['class' => null]]));

        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->willReturn($dummy)->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithControllerResultAndRemove()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'delete']);
        $request->setMethod('DELETE');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithSafeMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'head']);
        $request->setMethod('HEAD');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
    }

    public function testDoNotWriteWhenControllerResultIsResponse()
    {
        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports(Argument::cetera())->shouldNotBeCalled();
        $dataPersisterProphecy->persist(Argument::cetera())->shouldNotBeCalled();
        $dataPersisterProphecy->remove(Argument::cetera())->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request();

        $response = new Response();

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal());
        $listener->onKernelView($event);
    }

    public function testDoNotWriteWhenPersistFlagIsFalse()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports(Argument::cetera())->shouldNotBeCalled();
        $dataPersisterProphecy->persist(Argument::cetera())->shouldNotBeCalled();
        $dataPersisterProphecy->remove(Argument::cetera())->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post', '_api_persist' => false]);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        $listener = new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal());
        $listener->onKernelView($event);
    }

    public function testDoNotWriteWhenDisabledInOperationAttribute()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports(Argument::cetera())->shouldNotBeCalled();
        $dataPersisterProphecy->persist(Argument::cetera())->shouldNotBeCalled();
        $dataPersisterProphecy->remove(Argument::cetera())->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceMetadata = new ResourceMetadata('Dummy', null, null, [], [
            'post' => [
                'write' => false,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        $listener = new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);
    }

    public function testOnKernelViewWithNoResourceClass()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request();
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithParentResourceClass()
    {
        $dummy = new ConcreteDummy();

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->willReturn($dummy)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/dummy/1')->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => ConcreteDummy::class, '_api_item_operation_name' => 'put', '_api_persist' => true]);
        $request->setMethod('PUT');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithNoDataPersisterSupport()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(false)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Dummy', '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->onKernelView($event);
    }
}
