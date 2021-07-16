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
 * Deserialize stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface DeserializeStageInterface
{
    /**
     * @param object|null $objectToPopulate
     *
     * @return object|null
     */
    public function __invoke($objectToPopulate, string $resourceClass, string $operationName, array $context);
}
