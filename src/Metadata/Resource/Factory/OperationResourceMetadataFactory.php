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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Creates or completes operations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class OperationResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    /**
     * @internal
     */
    public const SUPPORTED_COLLECTION_OPERATION_METHODS = [
        'GET' => true,
        'POST' => true,
    ];

    /**
     * @internal
     */
    public const SUPPORTED_ITEM_OPERATION_METHODS = [
        'GET' => true,
        'PUT' => true,
        'DELETE' => true,
    ];

    private $decorated;
    private $formats;

    public function __construct(ResourceMetadataFactoryInterface $decorated, array $formats = [])
    {
        $this->decorated = $decorated;
        $this->formats = $formats;
    }

    /**
     * @internal
     */
    public static function populateOperations(string $resourceClass, ResourceMetadata $resourceMetadata, array $formats): ResourceMetadata
    {
        $isAbstract = (new \ReflectionClass($resourceClass))->isAbstract();

        $collectionOperations = $resourceMetadata->getCollectionOperations();
        if (null === $collectionOperations) {
            $resourceMetadata = $resourceMetadata->withCollectionOperations(static::createOperations(
                $isAbstract ? ['GET'] : ['GET', 'POST']
            ));
        } else {
            $resourceMetadata = static::normalize(true, $resourceMetadata, $collectionOperations, $formats);
        }

        $itemOperations = $resourceMetadata->getItemOperations();
        if (null === $itemOperations) {
            $methods = ['GET', 'DELETE'];

            if (!$isAbstract) {
                $methods[] = 'PUT';

                if (isset($formats['jsonapi'])) {
                    $methods[] = 'PATCH';
                }
            }

            $resourceMetadata = $resourceMetadata->withItemOperations(static::createOperations($methods));
        } else {
            $resourceMetadata = static::normalize(false, $resourceMetadata, $itemOperations, $formats);
        }

        return $resourceMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);
        $formats = $this->formats;
        $resourceMetadata = self::populateOperations($resourceClass, $resourceMetadata, $formats);

        $graphql = $resourceMetadata->getGraphql();
        if (null === $graphql) {
            $resourceMetadata = $resourceMetadata->withGraphql(['query' => [], 'delete' => [], 'update' => [], 'create' => []]);
        } else {
            $resourceMetadata = $this->normalizeGraphQl($resourceMetadata, $graphql);
        }

        return $resourceMetadata;
    }

    private static function createOperations(array $methods): array
    {
        $operations = [];
        foreach ($methods as $method) {
            $operations[strtolower($method)] = ['method' => $method];
        }

        return $operations;
    }

    private static function normalize(bool $collection, ResourceMetadata $resourceMetadata, array $operations, array $formats): ResourceMetadata
    {
        $newOperations = [];
        foreach ($operations as $operationName => $operation) {
            // e.g.: @ApiResource(itemOperations={"get"})
            if (\is_int($operationName) && \is_string($operation)) {
                $operationName = $operation;
                $operation = [];
            }

            $upperOperationName = strtoupper((string) $operationName);
            if ($collection) {
                $supported = isset(self::SUPPORTED_COLLECTION_OPERATION_METHODS[$upperOperationName]);
            } else {
                $supported = isset(self::SUPPORTED_ITEM_OPERATION_METHODS[$upperOperationName]) || (isset($formats['jsonapi']) && 'PATCH' === $upperOperationName);
            }

            if (!isset($operation['method']) && !isset($operation['route_name'])) {
                $supported ? $operation['method'] = $upperOperationName : $operation['route_name'] = $operationName;
            }

            $newOperations[$operationName] = $operation;
        }

        return $collection ? $resourceMetadata->withCollectionOperations($newOperations) : $resourceMetadata->withItemOperations($newOperations);
    }

    private function normalizeGraphQl(ResourceMetadata $resourceMetadata, array $operations): ResourceMetadata
    {
        foreach ($operations as $operationName => $operation) {
            if (\is_int($operationName) && \is_string($operation)) {
                unset($operations[$operationName]);
                $operations[$operation] = [];
            }
        }

        return $resourceMetadata->withGraphql($operations);
    }
}
