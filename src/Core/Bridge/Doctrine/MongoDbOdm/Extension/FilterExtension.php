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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\FilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Psr\Container\ContainerInterface;

/**
 * Applies filters on a resource aggregation.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements AggregationCollectionExtensionInterface
{
    use FilterLocatorTrait;

    private $resourceMetadataFactory;

    /**
     * @param ContainerInterface|FilterCollection $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, $filterLocator)
    {
        $this->setFilterLocator($filterLocator);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        if (empty($resourceFilters)) {
            return;
        }

        foreach ($resourceFilters as $filterId) {
            $filter = $this->getFilter($filterId);
            if ($filter instanceof FilterInterface) {
                $context['filters'] = $context['filters'] ?? [];
                $filter->apply($aggregationBuilder, $resourceClass, $operationName, $context);
            }
        }
    }
}
