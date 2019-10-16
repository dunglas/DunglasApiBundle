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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\RangeFilter;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\RangeFilterTestTrait;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class RangeFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use RangeFilterTestTrait;

    protected $filterClass = RangeFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'id[between]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'name[between]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[gt]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[gte]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[lt]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[lte]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'alias[between]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[gt]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[gte]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[lt]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[lte]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'description[between]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[gt]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[gte]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[lt]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[lte]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[between]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[gt]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[gte]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[lt]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[lte]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[between]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[gt]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[gte]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[lt]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[lte]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[between]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[gt]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[gte]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[lt]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[lte]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[between]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[gt]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[gte]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[lt]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[lte]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[between]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[gt]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[gte]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[lt]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[lte]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'arrayData[between]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
            ],
            'arrayData[gt]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
            ],
            'arrayData[gte]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
            ],
            'arrayData[lt]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
            ],
            'arrayData[lte]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[between]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[gt]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[gte]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[lt]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[lte]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[between]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[gt]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[gte]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[lt]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[lte]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummy[between]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummy[gt]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummy[gte]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummy[lt]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummy[lte]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummies[between]' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummies[gt]' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummies[gte]' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummies[lt]' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
            ],
            'relatedDummies[lte]' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwnedDummy[between]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwnedDummy[gt]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwnedDummy[gte]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwnedDummy[lt]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwnedDummy[lte]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwningDummy[between]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwningDummy[gt]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwningDummy[gte]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwningDummy[lt]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
            ],
            'relatedOwningDummy[lte]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'between' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$gte' => 9.99,
                                    '$lte' => 15.99,
                                ],
                            ],
                        ],
                    ],
                ],
                'between (too many operands)' => [
                    [],
                ],
                'between (too few operands)' => [
                    [],
                ],
                'between (non-numeric operands)' => [
                    [],
                ],
                'lt' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$lt' => 9.99,
                                ],
                            ],
                        ],
                    ],
                ],
                'lt (non-numeric)' => [
                    [],
                ],
                'lte' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$lte' => 9.99,
                                ],
                            ],
                        ],
                    ],
                ],
                'lte (non-numeric)' => [
                    [],
                ],
                'gt' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$gt' => 9.99,
                                ],
                            ],
                        ],
                    ],
                ],
                'gt (non-numeric)' => [
                    [],
                ],
                'gte' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$gte' => 9.99,
                                ],
                            ],
                        ],
                    ],
                ],
                'gte (non-numeric)' => [
                    [],
                ],
                'lte + gte' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$gte' => 9.99,
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$lte' => 19.99,
                                ],
                            ],
                        ],
                    ],
                ],
                'between (cast float to integer)' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$gte' => 9,
                                    '$lte' => 15,
                                ],
                            ],
                        ],
                    ],
                ],
                'lt (cast float to integer)' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$lt' => 15,
                                ],
                            ],
                        ],
                    ],
                ],
                'gt (cast float to integer)' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$gt' => 9,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
