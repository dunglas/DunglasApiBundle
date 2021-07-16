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
 * A function resolving a GraphQL mutation.
 *
 * @experimental
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 */
interface MutationResolverInterface
{
    /**
     * @param object|null $item
     *
     * @return object|null The mutated item
     */
    public function __invoke($item, array $context);
}
