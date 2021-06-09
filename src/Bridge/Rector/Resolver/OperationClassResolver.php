<?php

namespace ApiPlatform\Core\Bridge\Rector\Resolver;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

class OperationClassResolver
{
    private static $operationsClass = [
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

    public static function resolve($operationName, $operationType, &$items)
    {
        if (array_key_exists($operationName, self::$operationsClass[$operationType])) {
            return self::$operationsClass[$operationType][$operationName];
        }

        if (isset($items['method'])) {
            $method = strtolower($items['method']);
            unset($items['method']);

            if (isset(self::$operationsClass[$operationType][$method])) {
                return self::$operationsClass[$operationType][$method];
            }
        }

        throw new \Exception(sprintf('Unable to resolve operation class for %s "%s"', $operationType, $operationName));
    }
}
