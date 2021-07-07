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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\NumericFilterTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;

/**
 * Filters the collection by numeric values.
 *
 * Filters collection by equality of numeric properties.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not numeric, the property is ignored.
 *
 * @experimental
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class NumericFilter extends AbstractFilter
{
    use NumericFilterTrait;

    /**
     * Type of numeric in Doctrine.
     */
    public const DOCTRINE_NUMERIC_TYPES = [
        MongoDbType::INT => true,
        MongoDbType::INTEGER => true,
        MongoDbType::FLOAT => true,
    ];

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isNumericField($property, $resourceClass)
        ) {
            return;
        }

        $values = $this->normalizeValues($value, $property);
        if (null === $values) {
            return;
        }

        $matchField = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        if (1 === \count($values)) {
            $aggregationBuilder->match()->field($matchField)->equals($values[0]);
        } else {
            $aggregationBuilder->match()->field($matchField)->in($values);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType = null): string
    {
        if (null === $doctrineType) {
            return 'string';
        }

        if (MongoDbType::FLOAT === $doctrineType) {
            return 'float';
        }

        return 'int';
    }
}
