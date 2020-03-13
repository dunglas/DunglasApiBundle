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

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SerializerContextFactoryTest extends TestCase
{
    /**
     * @var SerializerContextFactory
     */
    private $serializerContextFactory;

    protected function setUp(): void
    {
        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            [],
            [],
            [
                'normalization_context' => ['foo' => 'bar'],
                'denormalization_context' => ['bar' => 'baz'],
            ]
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata);

        $this->serializerContextFactory = new SerializerContextFactory($resourceMetadataFactoryProphecy->reveal());
    }

    public function testCreate(): void
    {
        $context = ['item_operation_name' => 'get'];
        $expected = ['foo' => 'bar', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => null, 'operation_type' => 'item', 'uri' => null, 'output' => null, 'input' => null];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'get', true, $context));

        $context = ['collection_operation_name' => 'pot'];
        $expected = ['foo' => 'bar', 'collection_operation_name' => 'pot',  'resource_class' => 'Foo', 'request_uri' => null, 'operation_type' => 'collection', 'uri' => null, 'output' => null, 'input' => null];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'pot', true, $context));

        $context = ['item_operation_name' => 'get'];
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => null, 'api_allow_update' => false, 'operation_type' => 'item', 'uri' => null, 'output' => null, 'input' => null];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'get', false, $context));

        $context = ['collection_operation_name' => 'post'];
        $expected = ['bar' => 'baz', 'collection_operation_name' => 'post',  'resource_class' => 'Foo', 'request_uri' => null, 'api_allow_update' => false, 'operation_type' => 'collection', 'uri' => null, 'output' => null, 'input' => null];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'post', false, $context));

        $context = ['collection_operation_name' => 'put', 'request_method' => 'PUT'];
        $expected = ['bar' => 'baz', 'collection_operation_name' => 'put', 'resource_class' => 'Foo', 'request_uri' => null, 'api_allow_update' => true, 'operation_type' => 'collection', 'uri' => null, 'output' => null, 'input' => null];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'put', false, $context));

        $context = ['subresource_operation_name' => 'get'];
        $expected = ['bar' => 'baz', 'subresource_operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => null, 'operation_type' => 'subresource', 'api_allow_update' => false, 'uri' => null, 'output' => null, 'input' => null];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'get', false, $context));

        $context = ['resource_operation_name' => 'resource'];
        $expected = ['bar' => 'baz', 'resource_operation_name' => 'resource', 'resource_class' => 'Foo', 'request_uri' => null, 'operation_type' => 'resource', 'api_allow_update' => false, 'uri' => null, 'output' => null, 'input' => null];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'resource', false, $context));

        $context = ['item_operation_name' => 'get', 'request_content_type' => 'csv'];
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => null, 'api_allow_update' => false, 'operation_type' => 'item', 'uri' => null, 'output' => null, 'input' => null, 'as_collection' => false];
        $this->assertEquals($expected, $this->serializerContextFactory->create('Foo', 'get', false, $context));
    }
}
