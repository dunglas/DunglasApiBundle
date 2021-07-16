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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

/**
 * Interface for ordering the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface OrderFilterInterface
{
    public const DIRECTION_ASC = 'ASC';
    public const DIRECTION_DESC = 'DESC';
    public const NULLS_SMALLEST = 'nulls_smallest';
    public const NULLS_LARGEST = 'nulls_largest';
    public const NULLS_ALWAYS_FIRST = 'nulls_always_first';
    public const NULLS_ALWAYS_LAST = 'nulls_always_last';
    public const NULLS_DIRECTION_MAP = [
        self::NULLS_SMALLEST => [
            'ASC' => 'ASC',
            'DESC' => 'DESC',
        ],
        self::NULLS_LARGEST => [
            'ASC' => 'DESC',
            'DESC' => 'ASC',
        ],
        self::NULLS_ALWAYS_FIRST => [
            'ASC' => 'ASC',
            'DESC' => 'ASC',
        ],
        self::NULLS_ALWAYS_LAST => [
            'ASC' => 'DESC',
            'DESC' => 'DESC',
        ],
    ];
}
