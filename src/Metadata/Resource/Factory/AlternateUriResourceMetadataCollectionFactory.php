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

use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class AlternateUriResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
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

        foreach ($resourceMetadataCollection as $i => $resource) {
            if (0 === $i || ($resource->getExtraProperties()['is_legacy_subresource'] ?? false) || ($resource->getExtraProperties()['is_legacy_resource_metadata'] ?? false)) {
                continue;
            }

            $resourceMetadataCollection[$i] = $resource->withExtraProperties($resource->getExtraProperties() + ['is_alternate_resource_metadata' => true]);
            $operations = iterator_to_array($resource->getOperations());
            foreach ($operations as $key => $operation) {
                $operations[$key] = $operation->withExtraProperties($operation->getExtraProperties() + ['is_alternate_resource_metadata' => true]);
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return new ResourceMetadataCollection($resourceMetadataCollection);
    }
}
