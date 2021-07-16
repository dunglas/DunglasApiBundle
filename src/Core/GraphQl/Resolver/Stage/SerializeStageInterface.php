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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

/**
 * Serialize stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface SerializeStageInterface
{
    /**
     * @param object|iterable|null $itemOrCollection
     */
    public function __invoke($itemOrCollection, string $resourceClass, string $operationName, array $context): ?array;
}
