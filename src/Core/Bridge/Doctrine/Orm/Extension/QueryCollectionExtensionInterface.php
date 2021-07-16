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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface of Doctrine ORM query extensions for collection queries.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryCollectionExtensionInterface
{
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null);
}
