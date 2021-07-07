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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;

/**
 * Applies filters on a resource query.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements ContextAwareQueryCollectionExtensionInterface
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
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        if (empty($resourceFilters)) {
            return;
        }

        $orderFilters = [];

        foreach ($resourceFilters as $filterId) {
            $filter = $this->getFilter($filterId);
            if ($filter instanceof FilterInterface) {
                // Apply the OrderFilter after every other filter to avoid an edge case where OrderFilter would do a LEFT JOIN instead of an INNER JOIN
                if ($filter instanceof OrderFilter) {
                    $orderFilters[] = $filter;
                    continue;
                }

                $context['filters'] = $context['filters'] ?? [];
                $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            }
        }

        foreach ($orderFilters as $orderFilter) {
            $context['filters'] = $context['filters'] ?? [];
            $orderFilter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }
    }
}
