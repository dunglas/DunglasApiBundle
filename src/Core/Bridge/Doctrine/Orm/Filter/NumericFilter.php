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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\NumericFilterTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;

/**
 * Filters the collection by numeric values.
 *
 * Filters collection by equality of numeric properties.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not numeric, the property is ignored.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @final
 */
class NumericFilter extends AbstractContextAwareFilter
{
    use NumericFilterTrait;

    /**
     * Type of numeric in Doctrine.
     *
     * @see http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/types.html
     */
    public const DOCTRINE_NUMERIC_TYPES = [
        DBALType::BIGINT => true,
        DBALType::DECIMAL => true,
        DBALType::FLOAT => true,
        DBALType::INTEGER => true,
        DBALType::SMALLINT => true,
    ];

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
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

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        $valueParameter = $queryNameGenerator->generateParameterName($field);

        if (1 === \count($values)) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $values[0], (string) $this->getDoctrineFieldType($property, $resourceClass));
        } else {
            $queryBuilder
                ->andWhere(sprintf('%s.%s IN (:%s)', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $values);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType = null): string
    {
        if (null === $doctrineType || DBALType::DECIMAL === $doctrineType) {
            return 'string';
        }

        if (DBALType::FLOAT === $doctrineType) {
            return 'float';
        }

        return 'int';
    }
}
