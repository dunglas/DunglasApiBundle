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

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;

/**
 * Filters the collection by date intervals.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DateFilter extends AbstractFilter implements DateFilterInterface
{
    use DateFilterTrait;

    public const DOCTRINE_DATE_TYPES = [
        MongoDbType::DATE => true,
    ];

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        // Expect $values to be an array having the period as keys and the date value as values
        if (
            !\is_array($values) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isDateField($property, $resourceClass)
        ) {
            return;
        }

        $matchField = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        $nullManagement = $this->properties[$property] ?? null;

        if (self::EXCLUDE_NULL === $nullManagement) {
            $aggregationBuilder->match()->field($matchField)->notEqual(null);
        }

        if (isset($values[self::PARAMETER_BEFORE])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_BEFORE,
                $values[self::PARAMETER_BEFORE],
                $nullManagement,
                $property
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_BEFORE])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_STRICTLY_BEFORE,
                $values[self::PARAMETER_STRICTLY_BEFORE],
                $nullManagement,
                $property
            );
        }

        if (isset($values[self::PARAMETER_AFTER])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_AFTER,
                $values[self::PARAMETER_AFTER],
                $nullManagement,
                $property
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_AFTER])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_STRICTLY_AFTER,
                $values[self::PARAMETER_STRICTLY_AFTER],
                $nullManagement,
                $property
            );
        }
    }

    /**
     * Adds the match stage according to the chosen null management.
     */
    private function addMatch(Builder $aggregationBuilder, string $field, string $operator, string $value, ?string $nullManagement, string $property): void
    {
        if (null === $value = $this->normalizeValue($value, 'DateTime', $property, $operator)) {
            return;
        }

        $operatorValue = [
            self::PARAMETER_BEFORE => '$lte',
            self::PARAMETER_STRICTLY_BEFORE => '$lt',
            self::PARAMETER_AFTER => '$gte',
            self::PARAMETER_STRICTLY_AFTER => '$gt',
        ];

        if ((self::INCLUDE_NULL_BEFORE === $nullManagement && \in_array($operator, [self::PARAMETER_BEFORE, self::PARAMETER_STRICTLY_BEFORE], true)) ||
            (self::INCLUDE_NULL_AFTER === $nullManagement && \in_array($operator, [self::PARAMETER_AFTER, self::PARAMETER_STRICTLY_AFTER], true)) ||
            (self::INCLUDE_NULL_BEFORE_AND_AFTER === $nullManagement && \in_array($operator, [self::PARAMETER_BEFORE, self::PARAMETER_STRICTLY_BEFORE, self::PARAMETER_AFTER, self::PARAMETER_STRICTLY_AFTER], true))
        ) {
            $aggregationBuilder->match()->addOr(
                $aggregationBuilder->matchExpr()->field($field)->operator($operatorValue[$operator], $value),
                $aggregationBuilder->matchExpr()->field($field)->equals(null)
            );

            return;
        }

        $aggregationBuilder->match()->addAnd($aggregationBuilder->matchExpr()->field($field)->operator($operatorValue[$operator], $value));
    }
}
