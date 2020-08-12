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
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder as AggregationBuilder;

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

    public const DIRECTION_ASC = 'ASC';
    public const DIRECTION_DESC = 'DESC';

    private $order;
    private $resourceMetadataFactory;
    private $managerRegistry;

    public function __construct(string $order = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ManagerRegistry $managerRegistry = null)
    {
        $this->order = $order;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(AggregationBuilder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (null !== $this->resourceMetadataFactory) {
            if (null !== $order = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order')) {
                if (\is_string($order)) {
                    $direction = strtoupper($order);
                    if (\in_array($direction, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
                        $this->sortByAllIdentifiers($aggregationBuilder, $resourceClass, $direction, $context);

                        return;
                    }

                    $order = [$order];
                }

                foreach ($order as $property => $direction) {
                    if (\is_int($property)) {
                        $property = $direction;
                        $direction = self::DIRECTION_ASC;
                    }

                    $field = $property;

                    if ($this->isPropertyNested($property, $resourceClass)) {
                        [$field] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
                    }
                    $aggregationBuilder->sort(
                        $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$field => $direction]
                    );
                }

                return;
            }
        }

        if (null !== $this->order) {
            $this->sortByAllIdentifiers($aggregationBuilder, $resourceClass, $this->order, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    private function sortByAllIdentifiers(AggregationBuilder $aggregationBuilder, string $resourceClass, string $direction, array &$context): void
    {
        foreach ($this->getClassMetadata($resourceClass)->getIdentifier() as $field) {
            $aggregationBuilder->sort(
                $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$field => $direction]
            );
        }
    }
}
