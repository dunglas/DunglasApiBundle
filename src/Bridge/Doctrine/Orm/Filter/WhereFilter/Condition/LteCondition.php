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

final class LteCondition implements ConditionInterface
{
    public const TYPE = 'lte';

    private $key;

    private $values;

    public function __construct(string $key, array $values)
    {
        $this->key = $key;
        $this->values = $values;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(QueryBuilder $queryBuilder): Expr\Composite
    {
        $or = $queryBuilder->expr()->orX();
        foreach ($this->values as $value) {
            $or->add(
                $queryBuilder->expr()->lte($this->key, $queryBuilder->expr()->literal($value))
            );
        }

        return $or;
    }
}
