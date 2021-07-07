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

final class Paths
{
    private $paths;

    public function addPath(string $path, PathItem $pathItem)
    {
        $this->paths[$path] = $pathItem;
    }

    public function getPath(string $path): ?PathItem
    {
        return $this->paths[$path] ?? null;
    }

    public function getPaths(): array
    {
        return $this->paths ?? [];
    }
}
