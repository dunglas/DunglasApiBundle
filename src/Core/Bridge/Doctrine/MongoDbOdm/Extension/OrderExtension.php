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

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyHelperTrait as MongoDbOdmPropertyHelperTrait;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Applies selected ordering while querying resource collection.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class OrderExtension implements AggregationCollectionExtensionInterface
{
    use MongoDbOdmPropertyHelperTrait;
    use PropertyHelperTrait;

    private $order;
    private $resourceMetadataFactory;
    private $managerRegistry;

    public function __construct(string $order = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ManagerRegistry $managerRegistry = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->order = $order;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        $classMetaData = $this->getClassMetadata($resourceClass);
        $identifiers = $classMetaData->getIdentifier();
        if (null !== $this->resourceMetadataFactory) {
            $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)
                   ->getCollectionOperationAttribute($operationName, 'order', [], true);
            if (empty($defaultOrder)) {
                $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
            }
            if (null !== $defaultOrder) {
                foreach ($defaultOrder as $field => $order) {
                    if (\is_int($field)) {
                        // Default direction
                        $field = $order;
                        $order = 'ASC';
                    }

                    if ($this->isPropertyNested($field, $resourceClass)) {
                        [$field] = $this->addLookupsForNestedProperty($field, $aggregationBuilder, $resourceClass);
                    }
                    $aggregationBuilder->sort(
                        $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$field => $order]
                    );
                }

                return;
            }
        }

        if (null !== $this->order) {
            foreach ($identifiers as $identifier) {
                $aggregationBuilder->sort(
                    $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$identifier => $this->order]
                );
            }
        }
    }

    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }
}
