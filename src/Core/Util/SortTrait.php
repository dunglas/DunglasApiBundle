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

namespace ApiPlatform\Core\Util;

/**
 * Sort helper methods.
 *
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait SortTrait
{
    private function arrayRecursiveSort(array &$array, callable $sortFunction): void
    {
        foreach ($array as &$value) {
            if (\is_array($value)) {
                $this->arrayRecursiveSort($value, $sortFunction);
            }
        }
        unset($value);
        $sortFunction($array);
    }
}
