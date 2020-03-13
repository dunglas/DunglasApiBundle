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

use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class GroupFilterTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testLegacyApply(): void
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => ['foo', 'qux']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'qux', 'foo', 'bar', 'baz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithOverriding(): void
    {
        $request = new Request(['custom_groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => ['foo', 'qux']];

        $groupFilter = new GroupFilter('custom_groups', true);
        $groupFilter->apply($request, false, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar', 'baz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithoutGroupsInRequest(): void
    {
        $context = [AbstractNormalizer::GROUPS => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply(new Request(), false, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithGroupsWhitelist(): void
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('groups', false, ['foo', 'baz']);
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['qux', 'foo', 'baz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithGroupsWhitelistWithOverriding(): void
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('groups', true, ['foo', 'baz']);
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'baz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithGroupsInFilterAttribute(): void
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['groups' => ['fooz']]]);
        $context = ['groups' => ['foo', 'qux']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals(['groups' => ['foo', 'qux', 'fooz']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithInvalidGroupsInRequest(): void
    {
        $request = new Request(['groups' => 'foo,bar,baz']);
        $context = [AbstractNormalizer::GROUPS => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar']], $context);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyWithInvalidGroupsInContext(): void
    {
        $request = new Request(['custom_groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('custom_groups');
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testApply(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['groups' => ['foo', 'bar', 'baz']]];
        $serializerContext = [AbstractNormalizer::GROUPS => ['foo', 'qux']];

        $groupFilter = new GroupFilter();
        $groupFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'qux', 'foo', 'bar', 'baz']], $serializerContext);
    }

    public function testApplyWithOverriding(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['custom_groups' => ['foo', 'bar', 'baz']]];
        $serializerContext = [AbstractNormalizer::GROUPS => ['foo', 'qux']];

        $groupFilter = new GroupFilter('custom_groups', true);
        $groupFilter->applyToSerializerContext('Foo', 'get', false, $context, $serializerContext);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar', 'baz']], $serializerContext);
    }

    public function testApplyWithoutGroupsInContext(): void
    {
        $context = [];
        $serializerContext = [AbstractNormalizer::GROUPS => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->applyToSerializerContext('Foo', 'get', false, $context, $serializerContext);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar']], $serializerContext);
    }

    public function testApplyWithGroupsWhitelist(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['groups' => ['foo', 'bar', 'baz']]];
        $serializerContext = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('groups', false, ['foo', 'baz']);
        $groupFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['qux', 'foo', 'baz']], $serializerContext);
    }

    public function testApplyWithGroupsWhitelistWithOverriding(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['groups' => ['foo', 'bar', 'baz']]];
        $serializerContext = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('groups', true, ['foo', 'baz']);
        $groupFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'baz']], $serializerContext);
    }

    public function testApplyWithGroupsInFilterAttribute(): void
    {
        $context = ['filters' => ['groups' => ['fooz']], 'request_query' => ['groups' => ['foo', 'bar', 'baz']]];
        $serializerContext = ['groups' => ['foo', 'qux']];

        $groupFilter = new GroupFilter();
        $groupFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals(['groups' => ['foo', 'qux', 'fooz']], $serializerContext);
    }

    public function testApplyWithInvalidGroupsInContext(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['groups' => 'foo,bar,baz']];
        $serializerContext = [AbstractNormalizer::GROUPS => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar']], $serializerContext);
    }

    public function testApplyWithInvalidGroupsInSerializerContext(): void
    {
        $context = ['request_attributes' => [], 'request_query' => ['custom_groups' => ['foo', 'bar', 'baz']]];
        $serializerContext = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('custom_groups');
        $groupFilter->applyToSerializerContext('Foo', 'get', true, $context, $serializerContext);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['qux', 'foo', 'bar', 'baz']], $serializerContext);
    }

    public function testGetDescription(): void
    {
        $groupFilter = new GroupFilter('custom_groups');
        $expectedDescription = [
            'custom_groups[]' => [
                'property' => null,
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
            ],
        ];

        $this->assertEquals($expectedDescription, $groupFilter->getDescription(DummyGroup::class));
    }
}
