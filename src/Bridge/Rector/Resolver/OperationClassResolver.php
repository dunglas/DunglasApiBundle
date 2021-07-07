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

namespace ApiPlatform\Core\Bridge\Rector\Resolver;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

final class OperationClassResolver
{
    private static array $operationsClass = [
        'itemOperations' => [
            'get' => Get::class,
            'put' => Put::class,
            'patch' => Patch::class,
            'delete' => Delete::class,
            'post' => Post::class,
        ],
        'collectionOperations' => [
            'get' => GetCollection::class,
            'post' => Post::class,
        ],
    ];

    public static function resolve(string $operationName, string $operationType, array $arguments): string
    {
        if (\array_key_exists($operationName, self::$operationsClass[$operationType])) {
            return self::$operationsClass[$operationType][$operationName];
        }

        if (isset($arguments['method'], self::$operationsClass[$operationType][$method = strtolower($arguments['method'])])) {
            return self::$operationsClass[$operationType][$method];
        }

        throw new \Exception(sprintf('Unable to resolve operation class for %s "%s"', $operationType, $operationName));
    }
}
