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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\OrderDirection;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class OrderFilterTest extends DoctrineOrmFilterTestCase
{
    protected $filterClass = OrderFilter::class;

    public function testGetDescription()
    {
        $filter = new OrderFilter($this->managerRegistry, null, 'order', null, ['id' => null, 'name' => null, 'foo' => null]);
        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => OrderDirection::class,
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new OrderFilter($this->managerRegistry);

        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[alias]' => [
                'property' => 'alias',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[description]' => [
                'property' => 'description',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[dummy]' => [
                'property' => 'dummy',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[dummyFloat]' => [
                'property' => 'dummyFloat',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[dummyPrice]' => [
                'property' => 'dummyPrice',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[jsonData]' => [
                'property' => 'jsonData',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[arrayData]' => [
                'property' => 'arrayData',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[nameConverted]' => [
                'property' => 'nameConverted',
                'type' => OrderDirection::class,
                'required' => false,
            ],
            'order[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => OrderDirection::class,
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        $orderFilterFactory = function (ManagerRegistry $managerRegistry, RequestStack $requestStack = null, array $properties = null): OrderFilter {
            return new OrderFilter($managerRegistry, $requestStack, 'order', null, $properties);
        };
        $customOrderFilterFactory = function (ManagerRegistry $managerRegistry, RequestStack $requestStack = null, array $properties = null): OrderFilter {
            return new OrderFilter($managerRegistry, $requestStack, 'customOrder', null, $properties);
        };

        return [
            'valid values' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'desc',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'invalid values' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'invalid',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'valid values (properties not enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'alias' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'invalid values (properties not enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'invalid',
                        'name' => 'asc',
                        'alias' => 'invalid',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.name ASC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'invalid property (property not enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'invalid property (property enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                    'unknown' => null,
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'custom order parameter name' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'asc',
                    ],
                    'customOrder' => [
                        'name' => 'desc',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.name DESC', Dummy::class),
                null,
                $customOrderFilterFactory,
            ],
            'valid values (all properties enabled)' => [
                null,
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'asc',
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name ASC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.symfony' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'desc',
                        'relatedDummy.symfony' => 'desc',
                    ],
                ],
                sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 ORDER BY o.id ASC, o.name DESC, relatedDummy_a1.symfony DESC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'empty values with default sort direction' => [
                [
                    'id' => 'asc',
                    'name' => 'desc',
                ],
                [
                    'order' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
                sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'nulls_smallest (asc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_smallest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'asc',
                        'name' => 'desc',
                    ],
                ],
                sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate ASC, o.name DESC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'nulls_smallest (desc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_smallest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'desc',
                        'name' => 'desc',
                    ],
                ],
                sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate DESC, o.name DESC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'nulls_largest (asc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_largest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'asc',
                        'name' => 'desc',
                    ],
                ],
                sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate ASC, o.name DESC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'nulls_largest (desc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_largest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'desc',
                        'name' => 'desc',
                    ],
                ],
                sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate DESC, o.name DESC', Dummy::class),
                null,
                $orderFilterFactory,
            ],
            'not having order should not throw a deprecation (select unchanged)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'name' => 'q',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
                null,
                $orderFilterFactory,
            ],
        ];
    }
}
