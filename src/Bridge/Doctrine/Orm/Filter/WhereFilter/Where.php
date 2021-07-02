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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\AndCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\ConditionInterface;
use Doctrine\ORM\QueryBuilder;

final class Where
{
    /**
     * @var ConditionInterface[]
     */
    private $conditions = [];

    /**
     * @return ConditionInterface[]
     */
    public function getConditions(): array
    {
        return array_values($this->conditions);
    }

    public function addCondition(ConditionInterface $condition): void
    {
        if (isset($this->conditions[$condition->getType()])) {
            $currentCondition = $this->conditions[$condition->getType()];
            unset($this->conditions[$condition->getType()]);
            $condition = new AndCondition([$currentCondition, $condition]);
        }

        $this->conditions[$condition->getType()] = $condition;
    }

    public function apply(QueryBuilder $queryBuilder)
    {
        foreach ($this->conditions as $condition) {
            $queryBuilder->andWhere($condition->apply($queryBuilder));
        }
    }
}
