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

namespace ApiPlatform\Core\Operation\Factory;

use ApiPlatform\Core\Cache\CachedTrait;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
final class CachedSubresourceOperationFactory implements SubresourceOperationFactoryInterface
{
    use CachedTrait;

    public const CACHE_KEY_PREFIX = 'subresource_operations_';

    private $decorated;

    public function __construct(CacheItemPoolInterface $cacheItemPool, SubresourceOperationFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($resourceClass);

        return $this->getCached($cacheKey, function () use ($resourceClass) {
            return $this->decorated->create($resourceClass);
        });
    }
}
