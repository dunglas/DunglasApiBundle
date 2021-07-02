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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

final class OrCondition implements ConditionInterface
{
    public const TYPE = 'or';

    /**
     * @var ConditionInterface[]
     */
    private $conditions;

    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(QueryBuilder $queryBuilder): Expr\Composite
    {
        $or = $queryBuilder->expr()->orX();
        foreach ($this->conditions as $condition) {
            $or->add($condition->apply($queryBuilder));
        }

        return $or;
    }
}
