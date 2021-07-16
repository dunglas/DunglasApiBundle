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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\EagerLoadingTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Fixes filters on OneToMany associations
 * https://github.com/api-platform/core/issues/944.
 */
final class FilterEagerLoadingExtension implements ContextAwareQueryCollectionExtensionInterface
{
    use EagerLoadingTrait;

    private $resourceClassResolver;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, bool $forceEager = true, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->forceEager = $forceEager;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $em = $queryBuilder->getEntityManager();
        $classMetadata = $em->getClassMetadata($resourceClass);

        if (!$this->shouldOperationForceEager($resourceClass, ['collection_operation_name' => $operationName]) && !$this->hasFetchEagerAssociation($em, $classMetadata)) {
            return;
        }

        //If no where part, nothing to do
        $wherePart = $queryBuilder->getDQLPart('where');

        if (!$wherePart) {
            return;
        }

        $joinParts = $queryBuilder->getDQLPart('join');
        $originAlias = $queryBuilder->getRootAliases()[0];

        if (!$joinParts || !isset($joinParts[$originAlias])) {
            return;
        }

        $queryBuilderClone = clone $queryBuilder;
        $queryBuilderClone->resetDQLPart('where');
        $changedWhereClause = false;

        if (!$classMetadata->isIdentifierComposite) {
            $replacementAlias = $queryNameGenerator->generateJoinAlias($originAlias);
            $in = $this->getQueryBuilderWithNewAliases($queryBuilder, $queryNameGenerator, $originAlias, $replacementAlias);

            if ($classMetadata->containsForeignIdentifier) {
                $identifier = current($classMetadata->getIdentifier());
                $in->select("IDENTITY($replacementAlias.$identifier)");
                $queryBuilderClone->andWhere($queryBuilderClone->expr()->in("$originAlias.$identifier", $in->getDQL()));
            } else {
                $in->select($replacementAlias);
                $queryBuilderClone->andWhere($queryBuilderClone->expr()->in($originAlias, $in->getDQL()));
            }

            $changedWhereClause = true;
        } else {
            // Because Doctrine doesn't support WHERE ( foo, bar ) IN () (https://github.com/doctrine/doctrine2/issues/5238), we are building as many subqueries as they are identifiers
            foreach ($classMetadata->getIdentifier() as $identifier) {
                if (!$classMetadata->hasAssociation($identifier)) {
                    continue;
                }

                $replacementAlias = $queryNameGenerator->generateJoinAlias($originAlias);
                $in = $this->getQueryBuilderWithNewAliases($queryBuilder, $queryNameGenerator, $originAlias, $replacementAlias);
                $in->select("IDENTITY($replacementAlias.$identifier)");
                $queryBuilderClone->andWhere($queryBuilderClone->expr()->in("$originAlias.$identifier", $in->getDQL()));
                $changedWhereClause = true;
            }
        }

        if (false === $changedWhereClause) {
            return;
        }

        $queryBuilder->resetDQLPart('where');
        $queryBuilder->add('where', $queryBuilderClone->getDQLPart('where'));
    }

    /**
     * Returns a clone of the given query builder where everything gets re-aliased.
     *
     * @param string $originAlias the base alias
     * @param string $replacement the replacement for the base alias, will change the from alias
     */
    private function getQueryBuilderWithNewAliases(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $originAlias = 'o', string $replacement = 'o_2'): QueryBuilder
    {
        $queryBuilderClone = clone $queryBuilder;

        $joinParts = $queryBuilder->getDQLPart('join');
        $wherePart = $queryBuilder->getDQLPart('where');

        //reset parts
        $queryBuilderClone->resetDQLPart('join');
        $queryBuilderClone->resetDQLPart('where');
        $queryBuilderClone->resetDQLPart('orderBy');
        $queryBuilderClone->resetDQLPart('groupBy');
        $queryBuilderClone->resetDQLPart('having');

        //Change from alias
        $from = $queryBuilderClone->getDQLPart('from')[0];
        $queryBuilderClone->resetDQLPart('from');
        $queryBuilderClone->from($from->getFrom(), $replacement);

        $aliases = ["$originAlias."];
        $replacements = ["$replacement."];

        //Change join aliases
        foreach ($joinParts[$originAlias] as $joinPart) {
            /** @var Join $joinPart */
            $joinString = str_replace($aliases, $replacements, $joinPart->getJoin());
            $pos = strpos($joinString, '.');
            if (false === $pos) {
                if (null !== $joinPart->getCondition() && null !== $this->resourceClassResolver && $this->resourceClassResolver->isResourceClass($joinString)) {
                    $newAlias = $queryNameGenerator->generateJoinAlias($joinPart->getAlias());
                    $aliases[] = "{$joinPart->getAlias()}.";
                    $replacements[] = "$newAlias.";
                    $condition = str_replace($aliases, $replacements, $joinPart->getCondition());
                    $join = new Join($joinPart->getJoinType(), $joinPart->getJoin(), $newAlias, $joinPart->getConditionType(), $condition);
                    /* @phpstan-ignore-next-line */
                    $queryBuilderClone->add('join', [$replacement => $join], true);
                }

                continue;
            }
            $alias = substr($joinString, 0, $pos);
            $association = substr($joinString, $pos + 1);
            $newAlias = $queryNameGenerator->generateJoinAlias($association);
            $aliases[] = "{$joinPart->getAlias()}.";
            $replacements[] = "$newAlias.";
            $condition = str_replace($aliases, $replacements, $joinPart->getCondition() ?? '');
            QueryBuilderHelper::addJoinOnce($queryBuilderClone, $queryNameGenerator, $alias, $association, $joinPart->getJoinType(), $joinPart->getConditionType(), $condition, $originAlias, $newAlias);
        }

        $queryBuilderClone->add('where', str_replace($aliases, $replacements, (string) $wherePart));

        return $queryBuilderClone;
    }
}
