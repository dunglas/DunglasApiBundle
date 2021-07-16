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

namespace ApiPlatform\Core\OpenApi\Model;

trait ExtensionTrait
{
    private $extensionProperties = [];

    public function withExtensionProperty(string $key, $value)
    {
        if (0 !== strpos($key, 'x-')) {
            $key = 'x-'.$key;
        }

        $clone = clone $this;
        $clone->extensionProperties[$key] = $value;

        return $clone;
    }

    public function getExtensionProperties(): array
    {
        return $this->extensionProperties;
    }
}
