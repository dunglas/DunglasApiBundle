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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class LegacyResourceMetadataResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use DeprecationMetadataTrait;
    private $decorated;
    private $resourceMetadataFactory;
    private $defaults;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            return $resourceMetadataCollection;
        }

        $attributes = $resourceMetadata->getAttributes() ?? [];

        foreach ($this->defaults['attributes'] ?? [] as $key => $value) {
            if (!$value) {
                continue;
            }

            if (!isset($attributes[$key])) {
                $attributes[$key] = $value;
            }
        }

        $resource = (new ApiResource())
            ->withShortName($resourceMetadata->getShortName())
            ->withDescription($resourceMetadata->getDescription())
            ->withClass($resourceClass)
            ->withTypes([$resourceMetadata->getIri() ?? $resourceMetadata->getShortName()]);
        // ->withGraphql($resourceMetadata->getGraphql()); // TODO: fix this with graphql

        foreach ($attributes as $key => $value) {
            [$key, $value, $hasNoEquivalence] = $this->getKeyValue($key, $value);
            if ($hasNoEquivalence) {
                continue;
            }

            $resource = $resource->{'with'.ucfirst($key)}($value);
        }

        $operations = [];
        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM, $resource) as $operationName => $operation) {
            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName());
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION, $resource) as $operationName => $operation) {
            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName());
        }

        $resourceMetadataCollection[] = $resource->withOperations($operations);

        return $resourceMetadataCollection;
    }

    private function createOperations(array $operations, string $type, ApiResource $resource): iterable
    {
        $priority = 0;
        foreach ($operations as $operationName => $operation) {
            $newOperation = new Operation(method: $operation['method'], collection: OperationType::COLLECTION === $type, priority: $priority++);
            foreach ($operation as $key => $value) {
                [$key, $value, $hasNoEquivalence] = $this->getKeyValue($key, $value);
                if ($hasNoEquivalence) {
                    continue;
                }

                $newOperation = $newOperation->{'with'.ucfirst($key)}($value);
            }

            foreach (get_class_methods($resource) as $methodName) {
                if (0 !== strpos($methodName, 'get')) {
                    continue;
                }

                if (!method_exists($newOperation, $methodName)) {
                    continue;
                }

                $operationValue = $newOperation->{$methodName}();
                if (null !== $operationValue && [] !== $operationValue) {
                    continue;
                }

                $newOperation = $newOperation->{'with'.substr($methodName, 3)}($resource->{$methodName}());
            }

            // Default behavior in API Platform < 2.7
            if (null === $newOperation->getCompositeIdentifier()) {
                $newOperation = $newOperation->withCompositeIdentifier(true);
            }

            $newOperation = $newOperation->withExtraProperties($newOperation->getExtraProperties() + ['is_legacy_resource_metadata' => true]);
            // Avoiding operation name collision by adding _collection, this is rewritten by the UriTemplateResourceMetadataCollectionFactory
            yield sprintf('%s%s', $newOperation->getRouteName() ?? $operationName, OperationType::COLLECTION === $type ? '_collection' : '') => $newOperation;
        }
    }
}
