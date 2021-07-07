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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Cache\CachedTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches resource metadata.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class CachedResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use CachedTrait;
    public const CACHE_KEY_PREFIX = 'resource_metadata_collection_';

    private $decorated;
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;
    private $localCache = [];

    public function __construct(CacheItemPoolInterface $cacheItemPool, ResourceMetadataCollectionFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($resourceClass);
        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        try {
            $cacheCollection = $this->cacheItemPool->getItem($cacheKey);
        } catch (CacheException $e) {
            return $this->localCache[$cacheKey] = $this->decorated->create($resourceClass);
        }

        $resourceCollection = new ResourceMetadataCollection();
        if ($hasHit = $cacheCollection->isHit()) {
            $resourceCount = $cacheCollection->get();
            for ($i = 0; $i <= $resourceCount; ++$i) {
                $cacheItem = $this->cacheItemPool->getItem($cacheKey.$i);

                if ($hasHit = $cacheItem->isHit()) {
                    $resourceCollection[$i] = $cacheItem->get();

                    // find out why this is null ?!
                    if (null === $cacheItem->get()) {
                        return $this->localCache[$cacheKey] = $this->decorated->create($resourceClass);
                    }
                    continue;
                }
            }

            if ($hasHit) {
                return $this->localCache[$cacheKey] = $resourceCollection;
            }
        }

        // Warmup cache
        if (!$hasHit) {
            foreach ($this->decorated->create($resourceClass) as $i => $resourceMetadata) {
                $cacheItem = $this->cacheItemPool->getItem($cacheKey.$i);
                $resourceCollection[$i] = $resourceMetadata;
                $cacheItem->set($resourceMetadata);
                $this->cacheItemPool->save($cacheItem);
            }

            $cacheCollection->set(\count($resourceCollection));
            $this->cacheItemPool->save($cacheCollection);
            $this->cacheItemPool->commit();
        }

        return $this->localCache[$cacheKey] = $resourceCollection;
    }
}
