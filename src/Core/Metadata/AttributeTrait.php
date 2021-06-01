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

namespace ApiPlatform\Metadata;

/**
 * @experimental
 */
trait AttributeTrait
{
    public array $extraProperties;

    public function __get(string $name): mixed
    {
        return $this->extraProperties[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->extraProperties[$name] = $value;
    }
}
