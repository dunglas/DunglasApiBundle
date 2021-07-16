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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @internal
 *
 * @deprecated
 */
final class QueryJoinParser
{
    private function __construct()
    {
    }

    /**
     * Gets the class metadata from a given join alias.
     *
     * @deprecated
     */
    public static function getClassMetadataFromJoinAlias(string $alias, QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): ClassMetadata
    {
        @trigger_error(sprintf('The use of "%s::getClassMetadataFromJoinAlias()" is deprecated since 2.4 and will be removed in 3.0. Use "%s::getEntityClassByAlias()" instead.', __CLASS__, QueryBuilderHelper::class), \E_USER_DEPRECATED);

        $entityClass = QueryBuilderHelper::getEntityClassByAlias($alias, $queryBuilder, $managerRegistry);

        return $managerRegistry
            ->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass);
    }

    /**
     * Gets the relationship from a Join expression.
     *
     * @deprecated
     */
    public static function getJoinRelationship(Join $join): string
    {
        @trigger_error(sprintf('The use of "%s::getJoinRelationship()" is deprecated since 2.3 and will be removed in 3.0. Use "%s::getJoin()" directly instead.', __CLASS__, Join::class), \E_USER_DEPRECATED);

        return $join->getJoin();
    }

    /**
     * Gets the alias from a Join expression.
     *
     * @deprecated
     */
    public static function getJoinAlias(Join $join): string
    {
        @trigger_error(sprintf('The use of "%s::getJoinAlias()" is deprecated since 2.3 and will be removed in 3.0. Use "%s::getAlias()" directly instead.', __CLASS__, Join::class), \E_USER_DEPRECATED);

        return $join->getAlias();
    }

    /**
     * Gets the parts from an OrderBy expression.
     *
     * @return string[]
     *
     * @deprecated
     */
    public static function getOrderByParts(OrderBy $orderBy): array
    {
        @trigger_error(sprintf('The use of "%s::getOrderByParts()" is deprecated since 2.3 and will be removed in 3.0. Use "%s::getParts()" directly instead.', __CLASS__, OrderBy::class), \E_USER_DEPRECATED);

        return $orderBy->getParts();
    }
}
