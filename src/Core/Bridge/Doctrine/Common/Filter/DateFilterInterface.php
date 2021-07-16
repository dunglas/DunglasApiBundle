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
 * Interface for filtering the collection by date intervals.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface DateFilterInterface
{
    public const PARAMETER_BEFORE = 'before';
    public const PARAMETER_STRICTLY_BEFORE = 'strictly_before';
    public const PARAMETER_AFTER = 'after';
    public const PARAMETER_STRICTLY_AFTER = 'strictly_after';
    public const EXCLUDE_NULL = 'exclude_null';
    public const INCLUDE_NULL_BEFORE = 'include_null_before';
    public const INCLUDE_NULL_AFTER = 'include_null_after';
    public const INCLUDE_NULL_BEFORE_AND_AFTER = 'include_null_before_and_after';
}
