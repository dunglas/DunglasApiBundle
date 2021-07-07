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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;

/**
 * Helper trait regarding a property in an entity using the resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
trait PropertyHelperTrait
{
    /**
     * Splits the given property into parts.
     */
    abstract protected function splitPropertyParts(string $property/*, string $resourceClass*/): array;

    /**
     * Adds the necessary joins for a nested property.
     *
     * @throws InvalidArgumentException If property is not nested
     *
     * @return array An array where the first element is the join $alias of the leaf entity,
     *               the second element is the $field name
     *               the third element is the $associations array
     */
    protected function addJoinsForNestedProperty(string $property, string $rootAlias, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator/*, string $resourceClass, string $joinType*/): array
    {
        if (\func_num_args() > 4) {
            $resourceClass = func_get_arg(4);
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a fifth `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }
            $resourceClass = null;
        }

        if (\func_num_args() > 5) {
            $joinType = func_get_arg(5);
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a sixth `$joinType` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.3.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }
            $joinType = null;
        }

        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $parentAlias = $rootAlias;
        $alias = null;

        foreach ($propertyParts['associations'] as $association) {
            $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $parentAlias, $association, $joinType);
            $parentAlias = $alias;
        }

        if (null === $alias) {
            throw new InvalidArgumentException(sprintf('Cannot add joins for property "%s" - property is not nested.', $property));
        }

        return [$alias, $propertyParts['field'], $propertyParts['associations']];
    }
}
