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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\AndCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\EqCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\GtCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\GteCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\LtCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\LteCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\NeqCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\OrCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Where;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

final class WhereFilter implements ContextAwareFilterInterface
{
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (null === $filters = $context['filters']) {
            return;
        }

        if (null === $whereData = $filters['where']) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $where = $this->buildWhereFromArray($rootAlias, $whereData);

        $where->apply($queryBuilder);
    }

    public function getDescription(string $resourceClass): array
    {
        return []; // TODO
    }

    public function buildWhereFromArray(string $alias, array $whereArray): Where
    {
        $where = new Where();
        foreach ($whereArray as $conditionType => $conditionArray) {
            $type = array_keys($conditionArray)[0];

            switch ($type) {
                case AndCondition::TYPE:
                    $conditions = $this->buildWhereFromArray($alias, $conditionArray)->getConditions();
                    $where->addCondition(new AndCondition($conditions));
                    break;
                case OrCondition::TYPE:
                    $conditions = $this->buildWhereFromArray($alias, $conditionArray)->getConditions();
                    $where->addCondition(new OrCondition($conditions));
                    break;
                case EqCondition::TYPE:
                    $where->addCondition(new EqCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case NeqCondition::TYPE:
                    $where->addCondition(new NeqCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case GtCondition::TYPE:
                    $where->addCondition(new GtCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case GteCondition::TYPE:
                    $where->addCondition(new GteCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case LtCondition::TYPE:
                    $where->addCondition(new LtCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case LteCondition::TYPE:
                    $where->addCondition(new LteCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
            }
        }

        return $where;
    }
}
