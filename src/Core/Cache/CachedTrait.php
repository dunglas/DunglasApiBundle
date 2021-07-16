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

namespace ApiPlatform\Core\Cache;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
trait CachedTrait
{
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;
    private $localCache = [];

    private function getCached(string $cacheKey, callable $getValue)
    {
        if (\array_key_exists($cacheKey, $this->localCache)) {
            return $this->localCache[$cacheKey];
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        } catch (CacheException $e) {
            return $this->localCache[$cacheKey] = $getValue();
        }

        if ($cacheItem->isHit()) {
            return $this->localCache[$cacheKey] = $cacheItem->get();
        }

        $value = $getValue();

        $cacheItem->set($value);
        $this->cacheItemPool->save($cacheItem);

        return $this->localCache[$cacheKey] = $value;
    }
}
