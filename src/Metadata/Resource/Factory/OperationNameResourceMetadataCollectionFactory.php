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

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class OperationNameResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
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
            $operations = iterator_to_array($resource->getOperations());

            foreach ($resource->getOperations() as $operationName => $operation) {
                if ($operation->getRouteName()) {
                    $operations[$operationName] = $operation->withName($operation->getRouteName());
                    continue;
                }

                $newOperationName = sprintf('_api_%s_%s%s', $operation->getUriTemplate() ?: $operation->getShortName(), strtolower($operation->getMethod()), $operation->isCollection() ? '_collection' : '');

                // TODO: remove in 3.0 this is used in the IRI converter to avoid a bc break
                if (($extraProperties = $operation->getExtraProperties()) && isset($extraProperties['is_legacy_subresource'])) {
                    $extraProperties['legacy_subresource_operation_name'] = $newOperationName;
                    $operation = $operation->withExtraProperties($extraProperties);
                }

                unset($operations[$operationName]);
                $operations[$newOperationName] = $operation->withName($newOperationName);
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }
}
