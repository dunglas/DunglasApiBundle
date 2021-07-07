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

namespace ApiPlatform\Metadata;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class GraphQl
{
    /**
     * @readonly array $mutations
     * @readonly array $queries
     * @readonly array $subscriptions
     */
    public function __construct(
        public array $mutations = [],
        public array $queries = [],
        public array $subscriptions = [],
    ) {
    }
}
