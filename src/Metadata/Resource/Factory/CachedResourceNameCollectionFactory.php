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
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches resource name collection.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedResourceNameCollectionFactory implements LegacyResourceNameCollectionFactoryInterface
{
    use CachedTrait;

    public const CACHE_KEY = 'resource_name_collection';

    private $decorated;

    public function __construct(CacheItemPoolInterface $cacheItemPool, ResourceNameCollectionFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(bool $legacy = true): ResourceNameCollection
    {
        return $this->getCached($this->decorated instanceof LegacyResourceNameCollectionFactoryInterface ? sprintf('%s-%s', self::CACHE_KEY, $legacy) : self::CACHE_KEY, function () use ($legacy) {
            return $this->decorated instanceof LegacyResourceNameCollectionFactoryInterface ? $this->decorated->create($legacy) : $this->decorated->create();
        });
    }
}
