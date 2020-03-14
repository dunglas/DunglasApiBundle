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
    public function testApply()
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => ['foo', 'qux']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithOverriding()
    {
        $request = new Request(['custom_groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => ['foo', 'qux']];

        $groupFilter = new GroupFilter('custom_groups', true);
        $groupFilter->apply($request, false, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithoutGroupsInRequest()
    {
        $context = [AbstractNormalizer::GROUPS => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply(new Request(), false, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar']], $context);
    }

    public function testApplyWithGroupsWhitelist()
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('groups', false, ['foo', 'baz']);
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['qux', 'foo', 'baz']], $context);
    }

    public function testApplyWithGroupsWhitelistWithOverriding()
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('groups', true, ['foo', 'baz']);
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'baz']], $context);
    }

    public function testApplyWithGroupsInFilterAttribute()
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['groups' => ['fooz']]]);
        $context = ['groups' => ['foo', 'qux']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals(['groups' => ['foo', 'qux', 'fooz']], $context);
    }

    public function testApplyWithInvalidGroupsInRequest()
    {
        $request = new Request(['groups' => 'foo,bar,baz']);
        $context = [AbstractNormalizer::GROUPS => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['foo', 'bar']], $context);
    }

    public function testApplyWithInvalidGroupsInContext()
    {
        $request = new Request(['custom_groups' => ['foo', 'bar', 'baz']]);
        $context = [AbstractNormalizer::GROUPS => 'qux'];

        $groupFilter = new GroupFilter('custom_groups');
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals([AbstractNormalizer::GROUPS => ['qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testGetDescription()
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

    public function testGetDescriptionForWhitelistedGroups()
    {
        $groupFilter = new GroupFilter('whitlisted_groups', false, ['foo', 'bar']);
        $expectedDescription = [
            'whitlisted_groups[]' => [
                'property' => null,
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['foo', 'bar'],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedDescription, $groupFilter->getDescription(DummyGroup::class));
    }
}
