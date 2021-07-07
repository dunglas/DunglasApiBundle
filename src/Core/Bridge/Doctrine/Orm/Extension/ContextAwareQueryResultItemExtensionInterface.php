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
 * Context aware extension.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ContextAwareQueryResultItemExtensionInterface extends QueryResultItemExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null, array $context = []): bool;

    /**
     * {@inheritdoc}
     */
    public function getResult(QueryBuilder $queryBuilder, string $resourceClass = null, string $operationName = null, array $context = []);
}
