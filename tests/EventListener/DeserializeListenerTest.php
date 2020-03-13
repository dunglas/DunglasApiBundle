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

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\EventListener\DeserializeListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeserializeListenerTest extends TestCase
{
    private const FORMATS = ['json' => ['application/json']];

    public function testDoNotCallWhenRequestMethodIsSafe(): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()]);
        $request->setMethod('GET');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create(Argument::cetera())->shouldNotBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create()->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotCallWhenRequestNotManaged(): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()], [], [], [], '{}');
        $request->setMethod('POST');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create(Argument::cetera())->shouldNotBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create()->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotDeserializeWhenReceiveFlagIsFalse(): void
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create()->shouldNotBeCalled();

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotDeserializeWhenDisabledInOperationAttribute(): void
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $resourceMetadata = new ResourceMetadata('Dummy', null, null, [], [
            'post' => [
                'deserialize' => false,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserialize(string $method, bool $populateObject): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            ['put' => ['input_formats' => self::FORMATS]],
            ['post' => ['input_formats' => self::FORMATS]]
        ));

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'post', false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $this->doTestDeserialize($method, $populateObject, $resourceMetadataFactoryProphecy->reveal(), $serializerContextFactoryProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     *
     * @group legacy
     */
    public function testLegacyDeserialize(string $method, bool $populateObject): void
    {
        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(self::FORMATS)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $this->doTestDeserialize($method, $populateObject, $formatsProviderProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
    }

    private function doTestDeserialize(string $method, bool $populateObject, $resourceMetadataFactory, $serializerContextFactory): void
    {
        $result = $populateObject ? new \stdClass() : null;
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod($method);
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? [AbstractNormalizer::OBJECT_TO_POPULATE => $populateObject] : [];
        $context['input'] = ['class' => 'Foo'];
        $context['output'] = ['class' => 'Foo'];
        $context['resource_class'] = 'Foo';
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextFactory, $resourceMetadataFactory);
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserializeResourceClassSupportedFormat(string $method, bool $populateObject): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['post' => ['input_formats' => self::FORMATS]]
        ));

        $this->doTestDeserializeResourceClassSupportedFormat($method, $populateObject, $resourceMetadataFactoryProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testLegacyDeserializeResourceClassSupportedFormat(string $method, bool $populateObject): void
    {
        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes([
            'resource_class' => 'Foo',
            'collection_operation_name' => 'post',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ])->willReturn(self::FORMATS)->shouldBeCalled();

        $this->doTestDeserializeResourceClassSupportedFormat($method, $populateObject, $formatsProviderProphecy->reveal());
    }

    private function doTestDeserializeResourceClassSupportedFormat(string $method, bool $populateObject, $resourceMetadataFactory): void
    {
        $result = $populateObject ? new \stdClass() : null;
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod($method);
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? [AbstractNormalizer::OBJECT_TO_POPULATE => $populateObject] : [];
        $context['input'] = ['class' => 'Foo'];
        $context['output'] = ['class' => 'Foo'];
        $context['resource_class'] = 'Foo';
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'post', false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextFactoryProphecy->reveal(), $resourceMetadataFactory);

        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function methodProvider(): iterable
    {
        yield ['POST', false];
        yield ['PUT', true];
    }

    public function testContentNegotiation(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['post' => ['input_formats' => ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]]]
        ));

        $this->doTestContentNegotiation($resourceMetadataFactoryProphecy->reveal());
    }

    public function testLegacyContentNegotiation(): void
    {
        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']])->shouldBeCalled();

        $this->doTestContentNegotiation($formatsProviderProphecy->reveal());
    }

    private function doTestContentNegotiation($resourceMetadataFactory): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'text/xml');
        $request->setFormat('xml', 'text/xml'); // Workaround to avoid weird behaviors
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $context = ['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'];

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize('{}', 'Foo', 'xml', $context)->willReturn(new \stdClass())->shouldBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'post', false, Argument::type('array'))->willReturn($context)->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextFactoryProphecy->reveal(),
            $resourceMetadataFactory
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testNotSupportedContentType(): void
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage('The content-type "application/rdf+xml" is not supported. Supported MIME types are "application/ld+json", "text/xml".');

        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/rdf+xml');
        $request->setRequestFormat('xml');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'post', false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['post' => ['input_formats' => ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]]]
        ));

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testNoContentType(): void
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage('The "Content-Type" header must exist.');

        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->setRequestFormat('unknown');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create('Foo', 'post', false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, null, null, ['formats' => ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]]));

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an array or an instance of "ApiPlatform\Core\Api\FormatsProviderInterface" as 3rd parameter of the constructor of "ApiPlatform\Core\EventListener\DeserializeListener" is deprecated since API Platform 2.5, pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead
     */
    public function testLegacyFormatsParameter(): void
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextFactoryProphecy->reveal(),
            self::FORMATS
        );
    }
}
