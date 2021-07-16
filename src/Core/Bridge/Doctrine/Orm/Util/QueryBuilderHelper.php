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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * @internal
 */
final class QueryBuilderHelper
{
    private function __construct()
    {
    }

    /**
     * Adds a join to the QueryBuilder if none exists.
     */
    public static function addJoinOnce(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $association, string $joinType = null, string $conditionType = null, string $condition = null, string $originAlias = null, string $newAlias = null): string
    {
        $join = self::getExistingJoin($queryBuilder, $alias, $association, $originAlias);

        if (null !== $join) {
            return $join->getAlias();
        }

        $associationAlias = $newAlias ?? $queryNameGenerator->generateJoinAlias($association);
        $query = "$alias.$association";

        if (Join::LEFT_JOIN === $joinType || QueryChecker::hasLeftJoin($queryBuilder)) {
            $queryBuilder->leftJoin($query, $associationAlias, $conditionType, $condition);
        } else {
            $queryBuilder->innerJoin($query, $associationAlias, $conditionType, $condition);
        }

        return $associationAlias;
    }

    /**
     * Gets the entity class name by an alias used in the QueryBuilder.
     */
    public static function getEntityClassByAlias(string $alias, QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): string
    {
        if (!\in_array($alias, $queryBuilder->getAllAliases(), true)) {
            throw new \LogicException(sprintf('The alias "%s" does not exist in the QueryBuilder.', $alias));
        }

        $rootAliasMap = self::mapRootAliases($queryBuilder->getRootAliases(), $queryBuilder->getRootEntities());

        if (isset($rootAliasMap[$alias])) {
            return $rootAliasMap[$alias];
        }

        $metadata = null;

        foreach (self::traverseJoins($alias, $queryBuilder, $managerRegistry) as [$currentMetadata]) {
            $metadata = $currentMetadata;
        }

        if (null === $metadata) {
            throw new \LogicException(sprintf('The alias "%s" does not exist in the QueryBuilder.', $alias));
        }

        return $metadata->getName();
    }

    /**
     * Finds the root alias for an alias used in the QueryBuilder.
     */
    public static function findRootAlias(string $alias, QueryBuilder $queryBuilder): string
    {
        if (\in_array($alias, $queryBuilder->getRootAliases(), true)) {
            return $alias;
        }

        foreach ($queryBuilder->getDQLPart('join') as $rootAlias => $joins) {
            foreach ($joins as $join) {
                if ($alias === $join->getAlias()) {
                    return $rootAlias;
                }
            }
        }

        throw new \LogicException(sprintf('The alias "%s" does not exist in the QueryBuilder.', $alias));
    }

    /**
     * Traverses through the joins for an alias used in the QueryBuilder.
     *
     * @return \Generator<string, array>
     */
    public static function traverseJoins(string $alias, QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): \Generator
    {
        $rootAliasMap = self::mapRootAliases($queryBuilder->getRootAliases(), $queryBuilder->getRootEntities());

        $joinParts = $queryBuilder->getDQLPart('join');
        $rootAlias = self::findRootAlias($alias, $queryBuilder);

        $joinAliasMap = self::mapJoinAliases($joinParts[$rootAlias]);

        $aliasMap = array_merge($rootAliasMap, $joinAliasMap);

        $apexEntityClass = null;
        $associationStack = [];
        $aliasStack = [];
        $currentAlias = $alias;

        while (null === $apexEntityClass) {
            if (!isset($aliasMap[$currentAlias])) {
                throw new \LogicException(sprintf('Unknown alias "%s".', $currentAlias));
            }

            if (\is_string($aliasMap[$currentAlias])) {
                $aliasStack[] = $currentAlias;
                $apexEntityClass = $aliasMap[$currentAlias];
            } else {
                [$parentAlias, $association] = $aliasMap[$currentAlias];

                $associationStack[] = $association;
                $aliasStack[] = $currentAlias;
                $currentAlias = $parentAlias;
            }
        }

        $entityClass = $apexEntityClass;

        while (null !== ($alias = array_pop($aliasStack))) {
            $metadata = $managerRegistry
                ->getManagerForClass($entityClass)
                ->getClassMetadata($entityClass);

            $association = array_pop($associationStack);

            yield $alias => [
                $metadata,
                $association,
            ];

            if (null !== $association) {
                $entityClass = $metadata->getAssociationTargetClass($association);
            }
        }
    }

    /**
     * Gets the existing join from QueryBuilder DQL parts.
     */
    public static function getExistingJoin(QueryBuilder $queryBuilder, string $alias, string $association, string $originAlias = null): ?Join
    {
        $parts = $queryBuilder->getDQLPart('join');
        $rootAlias = $originAlias ?? $queryBuilder->getRootAliases()[0];

        if (!isset($parts[$rootAlias])) {
            return null;
        }

        foreach ($parts[$rootAlias] as $join) {
            /** @var Join $join */
            if (sprintf('%s.%s', $alias, $association) === $join->getJoin()) {
                return $join;
            }
        }

        return null;
    }

    /**
     * Maps the root aliases to root entity classes.
     *
     * @return array<string, string>
     */
    private static function mapRootAliases(array $rootAliases, array $rootEntities): array
    {
        /** @var false|array $aliasMap */
        $aliasMap = array_combine($rootAliases, $rootEntities);
        if (false === $aliasMap) {
            throw new \LogicException('Number of root aliases and root entities do not match.');
        }

        return $aliasMap;
    }

    /**
     * Maps the join aliases to the parent alias and association, or the entity class.
     *
     * @return array<string, string[]|string>
     */
    private static function mapJoinAliases(iterable $joins): array
    {
        $aliasMap = [];

        foreach ($joins as $join) {
            $alias = $join->getAlias();
            $relationship = $join->getJoin();

            if (false !== strpos($relationship, '.')) {
                $aliasMap[$alias] = explode('.', $relationship);
            } else {
                $aliasMap[$alias] = $relationship;
            }
        }

        return $aliasMap;
    }
}
