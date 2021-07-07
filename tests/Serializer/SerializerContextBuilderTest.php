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

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Serializer\SerializerContextBuilder;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SerializerContextBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $resourceMetadata = new ResourceCollection([
            new Resource(
                uriTemplate: null,
                shortName: null,
                description: null,
                types: [],
                operations: [],
                normalizationContext: ['foo' => 'bar', DocumentationNormalizer::SWAGGER_DEFINITION_NAME => 'MyDefinition'],
                denormalizationContext: ['bar' => 'baz']
            ), ]);

        $resourceMetadataWithPatch = new ResourceCollection([
            new Resource(
                uriTemplate: null,
                shortName: null,
                description: null,
                operations: [
                    'patch' => new Patch(
                        inputFormats: ['json' => ['application/merge-patch+json']],
                    ),
                ],
                formats: []
            ),
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata);
        $resourceMetadataFactoryProphecy->create('FooWithPatch')->willReturn($resourceMetadataWithPatch);

        $this->builder = new SerializerContextBuilder($resourceMetadataFactoryProphecy->reveal());
    }

    public function testCreateFromRequest()
    {
        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'operation_type' => 'item', 'uri' => 'http://localhost/foos/1', 'output' => null, 'input' => null, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'pot', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['foo' => 'bar', 'collection_operation_name' => 'pot',  'resource_class' => 'Foo', 'request_uri' => '/foos', 'operation_type' => 'collection', 'uri' => 'http://localhost/foos', 'output' => null, 'input' => null, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, true));

        $request = Request::create('/foos/1');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get',  'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'api_allow_update' => false, 'operation_type' => 'item', 'uri' => 'http://localhost/foos/1', 'output' => null, 'input' => null, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'POST');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'collection_operation_name' => 'post',  'resource_class' => 'Foo', 'request_uri' => '/foos', 'api_allow_update' => false, 'operation_type' => 'collection', 'uri' => 'http://localhost/foos', 'output' => null, 'input' => null, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foos', 'PUT');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'put', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'collection_operation_name' => 'put', 'resource_class' => 'Foo', 'request_uri' => '/foos', 'api_allow_update' => true, 'operation_type' => 'collection', 'uri' => 'http://localhost/foos', 'output' => null, 'input' => null, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/bars/1/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml']);
        $expected = ['bar' => 'baz', 'subresource_operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/bars/1/foos', 'operation_type' => 'subresource', 'api_allow_update' => false, 'uri' => 'http://localhost/bars/1/foos', 'output' => null, 'input' => null, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/foowithpatch/1', 'PATCH');
        $request->attributes->replace(['_api_resource_class' => 'FooWithPatch', '_api_item_operation_name' => 'patch', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $expected = ['item_operation_name' => 'patch', 'resource_class' => 'FooWithPatch', 'request_uri' => '/foowithpatch/1', 'operation_type' => 'item', 'api_allow_update' => true, 'uri' => 'http://localhost/foowithpatch/1', 'output' => null, 'input' => null, 'deep_object_to_populate' => true, 'skip_null_values' => true, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));

        $request = Request::create('/bars/1/foos');
        $request->attributes->replace(['_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'xml', '_api_mime_type' => 'text/xml', '_api_subresource_context' => ['identifiers' => ['id' => ['Foo', 'id']]], 'id' => '1']);
        $expected = ['bar' => 'baz', 'subresource_operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/bars/1/foos', 'operation_type' => 'subresource', 'api_allow_update' => false, 'uri' => 'http://localhost/bars/1/foos', 'output' => null, 'input' => null, 'iri_only' => false, 'subresource_identifiers' => ['id' => '1'], 'subresource_resources' => ['Foo' => ['id' => '1']]];
        $this->assertEquals($expected, $this->builder->createFromRequest($request, false));
    }

    public function testThrowExceptionOnInvalidRequest()
    {
        $this->expectException(RuntimeException::class);

        $this->builder->createFromRequest(new Request(), false);
    }

    public function testReuseExistingAttributes()
    {
        $expected = ['bar' => 'baz', 'item_operation_name' => 'get', 'resource_class' => 'Foo', 'request_uri' => '/foos/1', 'api_allow_update' => false, 'operation_type' => 'item', 'uri' => 'http://localhost/foos/1', 'output' => null, 'input' => null, 'iri_only' => false];
        $this->assertEquals($expected, $this->builder->createFromRequest(Request::create('/foos/1'), false, ['resource_class' => 'Foo', 'item_operation_name' => 'get']));
    }
}
