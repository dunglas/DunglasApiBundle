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
final class CachedResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
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
    public function create(): ResourceNameCollection
    {
        return $this->getCached(self::CACHE_KEY, function () {
            return $this->decorated->create();
        });
    }
}
