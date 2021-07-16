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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Helps creating metadata on the Resource based on the properties of this same resource. Computes "identifiers".
 * TODO: compute serialization groups as in SerializerPropertyMetadataFactory using the same injected services trying to avoid recursion.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class IdentifierResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        $identifiers = null;
        foreach ($resourceMetadataCollection as $i => $resource) {
            if (!$resource->getIdentifiers()) {
                $resource = $resource->withIdentifiers($identifiers ?: ($identifiers = $this->getIdentifiersFromResourceClass($resourceClass)));
            } else {
                $identifiers = $resource->getIdentifiers();
            }

            $resource = $resource->withIdentifiers($this->normalizeIdentifiers($resource->getIdentifiers(), $resourceClass));

            // Copy identifiers to operations if not defined
            foreach ($resource->getOperations() as $key => $operation) {
                if ($identifiers && !$operation->getIdentifiers() && !$operation->isCollection()) {
                    $operation = $operation->withIdentifiers($identifiers);
                }

                $operations = iterator_to_array($resource->getOperations());
                $operations[$key] = $operation->withIdentifiers($this->normalizeIdentifiers($operation->getIdentifiers(), $resourceClass));
                $resource = $resource->withOperations($operations);
            }

            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
    }

    private function normalizeIdentifiers(mixed $identifiers, string $resourceClass): array
    {
        if (!$identifiers) {
            return [];
        }

        if (\is_string($identifiers)) {
            return [$identifiers => [$resourceClass, $identifiers]];
        }

        $normalized = [];

        foreach ($identifiers as $parameterName => $identifier) {
            if (\is_int($parameterName)) {
                $normalized[$identifier] = [$resourceClass, $identifier];
            } elseif (\is_string($identifier)) {
                $normalized[$parameterName] = [$resourceClass, $identifier];
            } else {
                $normalized[$parameterName] = $identifier;
            }
        }

        return $normalized;
    }

    private function getIdentifiersFromResourceClass(string $resourceClass): array
    {
        $identifiers = [];
        $hasIdProperty = false;
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $hasIdProperty = 'id' === $property;
            if ($this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier() ?? false) {
                $identifiers[$property] = [$resourceClass, $property];
            }
        }

        if (!$identifiers) {
            if ($hasIdProperty) {
                return ['id' => [$resourceClass, 'id']];
            }

            return $identifiers;
        }

        return $identifiers;
    }
}
