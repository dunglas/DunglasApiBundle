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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

/**
 * Helps creating metadata on the Resource based on the properties of this same resource. Computes "identifiers".
 * TODO: compute serialization groups as in SerializerPropertyMetadataFactory using the same injected services trying to avoid recursion.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class IdentifierResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);
        $identifiers = null;

        foreach ($resourceMetadataCollection as $i => $resource) {
            if (!$resource->identifiers) {
                $resource->identifiers = $identifiers ?: ($identifiers = $this->getIdentifiersFromResourceClass($resourceClass));

                foreach ($resource->operations as $key => $operation) {
                    if (!$operation->identifiers && !$operation instanceof Post && !$operation instanceof GetCollection) {
                        $resource->operations[$key]->identifiers = $identifiers;
                    }
                }

                $resourceMetadataCollection[$i] = $resource;
                continue;
            }

            if (!\is_string(current($resource->identifiers))) {
                continue;
            }

            $formatted = [];
            foreach ($resource->identifiers as $identifier) {
                $formatted[$identifier] = [$identifier => $resourceClass];
            }

            $resource->identifiers = $formatted;

            foreach ($resource->operations as $key => $operation) {
                if (!$operation->identifiers) {
                    $resource->operations[$key]->identifiers = $formatted;
                }
            }

            $resourceMetadataCollection[$i] = $resource;
        }

        return new ResourceCollection($resourceMetadataCollection);
    }

    private function getIdentifiersFromResourceClass(string $resourceClass): array
    {
        $identifiers = [];
        $hasIdProperty = false;
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $hasIdProperty = 'id' === $property;
            try {
                if ($this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier() ?? false) {
                    $identifiers[$property] = [$resourceClass, $property];
                }
            } catch (PropertyNotFoundException $e) {
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
