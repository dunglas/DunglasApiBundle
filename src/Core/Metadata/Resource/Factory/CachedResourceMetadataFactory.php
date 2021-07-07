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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Cache\CachedTrait;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches resource metadata.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    use CachedTrait;

    public const CACHE_KEY_PREFIX = 'resource_metadata_';

    private $decorated;

    public function __construct(CacheItemPoolInterface $cacheItemPool, ResourceMetadataFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($resourceClass);

        return $this->getCached($cacheKey, function () use ($resourceClass) {
            return $this->decorated->create($resourceClass);
        });
    }
}
