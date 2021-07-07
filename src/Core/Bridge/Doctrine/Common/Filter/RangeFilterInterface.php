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
 * Interface for filtering the collection by range.
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface RangeFilterInterface
{
    public const PARAMETER_BETWEEN = 'between';
    public const PARAMETER_GREATER_THAN = 'gt';
    public const PARAMETER_GREATER_THAN_OR_EQUAL = 'gte';
    public const PARAMETER_LESS_THAN = 'lt';
    public const PARAMETER_LESS_THAN_OR_EQUAL = 'lte';
}
