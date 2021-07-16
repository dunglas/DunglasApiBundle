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

namespace ApiPlatform\Core\GraphQl\Resolver;

/**
 * A function resolving a GraphQL query of a collection.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface QueryCollectionResolverInterface
{
    /**
     * @param iterable<object> $collection
     *
     * @return iterable<object>
     */
    public function __invoke(iterable $collection, array $context): iterable;
}
