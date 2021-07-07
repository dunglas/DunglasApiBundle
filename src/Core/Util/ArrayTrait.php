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

trait ArrayTrait
{
    public function isSequentialArrayOfArrays(array $array): bool
    {
        if (!$this->isSequentialArray($array)) {
            return false;
        }

        return $this->arrayContainsOnly($array, 'array');
    }

    public function isSequentialArray(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_keys($array) === range(0, \count($array) - 1);
    }

    public function arrayContainsOnly(array $array, string $type): bool
    {
        return $array === array_filter($array, static function ($item) use ($type): bool {
            return $type === \gettype($item);
        });
    }
}
