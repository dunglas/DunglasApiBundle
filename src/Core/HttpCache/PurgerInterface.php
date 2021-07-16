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

namespace ApiPlatform\Core\HttpCache;

/**
 * Purges resources from the cache.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
interface PurgerInterface
{
    /**
     * Purges all responses containing the given resources from the cache.
     *
     * @param string[] $iris
     */
    public function purge(array $iris);
}
