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

namespace ApiPlatform\Core\Tests\Serializer\Filter;

use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class PropertyFilterTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testLegacyApply(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'foo', 'bar', 'baz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithOverriding(): void
    {
        $request = new Request(['custom_properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true);
        $propertyFilter->apply($request, false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'baz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithoutPropertiesInRequest(): void
    {
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelist(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['bar', 'fuz', 'foo']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'bar']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelistAndNestedProperty(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'group' => ['baz' => ['qux']]]);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'group' => ['baz' => ['qux']]]], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelistNotMatchingAnyProperty(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelistAndOverriding(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'baz']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'baz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesInPropertyFilterAttribute(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['properties' => ['fooz']]]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'fooz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithInvalidPropertiesInRequest(): void
    {
        $request = new Request(['properties' => 'foo,bar,baz']);
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'name_converted', 'foo', 'bar', 'nameConverted']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithOverridingAndNameConverter(): void
    {
        $request = new Request(['custom_properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true, null, new CustomConverter());
        $propertyFilter->apply($request, false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'nameConverted']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithoutPropertiesInRequestAndNameConverter(): void
    {
        $context = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'name_converted']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelistAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'name_converted', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['nameConverted', 'fuz', 'foo'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelistWithNestedPropertyAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'nameConverted' => ['baz' => ['nameConverted']]], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted' => ['baz' => ['nameConverted']]]], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelistNotMatchingAnyPropertyAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesWhitelistAndOverridingAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'nameConverted'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'nameConverted']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithPropertiesInPropertyFilterAttributeAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['properties' => ['name_converted']]]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'nameConverted']], $context);
    }

    public function testApply(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'baz']]];
        $serializerContext = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'foo', 'bar', 'baz']], $serializerContext);
    }

    public function testApplyWithOverriding(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['custom_properties' => ['foo', 'bar', 'baz']]];
        $serializerContext = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true);
        $propertyFilter->applyToSerializerContext('Foo', 'get', false, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'baz']], $serializerContext);
    }

    public function testApplyWithoutPropertiesInContext(): void
    {
        $context = ['request_attributes' => [], 'request_query' => []];
        $serializerContext = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->applyToSerializerContext('Foo', 'get', false, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelist(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'baz']]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['bar', 'fuz', 'foo']);
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'bar']], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelistAndNestedProperty(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'group' => ['baz' => ['qux']]]);
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'group' => ['baz' => ['qux']]]], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelistNotMatchingAnyProperty(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz']);
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['qux']], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelistAndOverriding(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'baz']]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'baz']);
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'baz']], $serializerContext);
    }

    public function testApplyWithPropertiesInPropertyFilterAttribute(): void
    {
        $context = ['request_attributes' => ['_api_filters' => ['properties' => ['fooz']]], 'request_query' => ['properties' => ['foo', 'bar', 'baz']]];
        $serializerContext = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'fooz']], $serializerContext);
    }

    public function testApplyWithInvalidPropertiesInContext(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => 'foo,bar,baz']];
        $serializerContext = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $serializerContext);
    }

    public function testApplyWithNameConverter(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'name_converted']]];
        $serializerContext = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'name_converted', 'foo', 'bar', 'nameConverted']], $serializerContext);
    }

    public function testApplyWithOverridingAndNameConverter(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['custom_properties' => ['foo', 'bar', 'name_converted']]];
        $serializerContext = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true, null, new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', false, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'nameConverted']], $serializerContext);
    }

    public function testApplyWithoutPropertiesInContextAndNameConverter(): void
    {
        $context = [];
        $serializerContext = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', false, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'name_converted']], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelistAndNameConverter(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'name_converted', 'baz']]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['nameConverted', 'fuz', 'foo'], new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted']], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelistWithNestedPropertyAndNameConverter(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'nameConverted' => ['baz' => ['nameConverted']]], new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted' => ['baz' => ['nameConverted']]]], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelistNotMatchingAnyPropertyAndNameConverter(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz'], new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['qux']], $serializerContext);
    }

    public function testApplyWithPropertiesWhitelistAndOverridingAndNameConverter(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['properties' => ['foo', 'bar', 'name_converted']]];
        $serializerContext = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'nameConverted'], new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'nameConverted']], $serializerContext);
    }

    public function testApplyWithPropertiesInPropertyFilterAttributeAndNameConverter(): void
    {
        $context = ['request_attributes' => ['_api_filters' => ['properties' => ['name_converted']]], 'request_query' => ['properties' => ['foo', 'bar', 'baz']]];
        $serializerContext = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'nameConverted']], $serializerContext);
    }

    public function testGetDescription(): void
    {
        $propertyFilter = new PropertyFilter('custom_properties');
        $expectedDescription = [
            'custom_properties[]' => [
                'property' => null,
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
                'swagger' => [
                    'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: custom_properties[]={propertyName}&custom_properties[]={anotherPropertyName}&custom_properties[{nestedPropertyParent}][]={nestedProperty}',
                    'name' => 'custom_properties[]',
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'openapi' => [
                    'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: custom_properties[]={propertyName}&custom_properties[]={anotherPropertyName}&custom_properties[{nestedPropertyParent}][]={nestedProperty}',
                    'name' => 'custom_properties[]',
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedDescription, $propertyFilter->getDescription(DummyProperty::class));
    }
}
