<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Rector\Resolver;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

class OperationClassResolver
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
        if (array_key_exists($operationName, self::$operationsClass[$operationType])) {
            return self::$operationsClass[$operationType][$operationName];
        }

        if (isset($arguments['method'])) {
            $method = strtolower($arguments['method']);

            if (isset(self::$operationsClass[$operationType][$method])) {
                return self::$operationsClass[$operationType][$method];
            }
        }

        throw new \Exception(sprintf('Unable to resolve operation class for %s "%s"', $operationType, $operationName));
    }
}
