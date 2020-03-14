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
    public function testApply()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithOverriding()
    {
        $request = new Request(['custom_properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true);
        $propertyFilter->apply($request, false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithoutPropertiesInRequest()
    {
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    public function testApplyWithPropertiesWhitelist()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['bar', 'fuz', 'foo']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'bar']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndNestedProperty()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'group' => ['baz' => ['qux']]]);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'group' => ['baz' => ['qux']]]], $context);
    }

    public function testApplyWithPropertiesWhitelistNotMatchingAnyProperty()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndOverriding()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'baz']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'baz']], $context);
    }

    public function testApplyWithPropertiesInPropertyFilterAttribute()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['properties' => ['fooz']]]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'fooz']], $context);
    }

    public function testApplyWithInvalidPropertiesInRequest()
    {
        $request = new Request(['properties' => 'foo,bar,baz']);
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    public function testApplyWithNameConverter()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'name_converted', 'foo', 'bar', 'nameConverted']], $context);
    }

    public function testApplyWithOverridingAndNameConverter()
    {
        $request = new Request(['custom_properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true, null, new CustomConverter());
        $propertyFilter->apply($request, false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'nameConverted']], $context);
    }

    public function testApplyWithoutPropertiesInRequestAndNameConverter()
    {
        $context = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'name_converted']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndNameConverter()
    {
        $request = new Request(['properties' => ['foo', 'name_converted', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['nameConverted', 'fuz', 'foo'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted']], $context);
    }

    public function testApplyWithPropertiesWhitelistWithNestedPropertyAndNameConverter()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'nameConverted' => ['baz' => ['nameConverted']]], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted' => ['baz' => ['nameConverted']]]], $context);
    }

    public function testApplyWithPropertiesWhitelistNotMatchingAnyPropertyAndNameConverter()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndOverridingAndNameConverter()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'nameConverted'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'nameConverted']], $context);
    }

    public function testApplyWithPropertiesInPropertyFilterAttributeAndNameConverter()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['properties' => ['name_converted']]]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'nameConverted']], $context);
    }

    public function testGetDescription()
    {
        $propertyFilter = new PropertyFilter('custom_properties');
        $expectedDescription = [
            'custom_properties[]' => [
                'property' => null,
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
                'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: custom_properties[]={propertyName}&custom_properties[]={anotherPropertyName}&custom_properties[{nestedPropertyParent}][]={nestedProperty}',
            ],
        ];

        $this->assertEquals($expectedDescription, $propertyFilter->getDescription(DummyProperty::class));
    }
}
