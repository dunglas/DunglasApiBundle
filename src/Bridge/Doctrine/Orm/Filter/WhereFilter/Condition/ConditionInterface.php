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

use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;

interface ConditionInterface
{
    public function getType(): string;

    public function apply(QueryBuilder $queryBuilder): Composite;
}
