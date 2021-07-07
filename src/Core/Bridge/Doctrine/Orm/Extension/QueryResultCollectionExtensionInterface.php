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

use Doctrine\ORM\QueryBuilder;

/**
 * Interface of Doctrine ORM query extensions that supports result production
 * for specific cases such as pagination.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryResultCollectionExtensionInterface extends QueryCollectionExtensionInterface
{
    public function supportsResult(string $resourceClass, string $operationName = null): bool;

    /**
     * @return iterable
     */
    public function getResult(QueryBuilder $queryBuilder);
}
