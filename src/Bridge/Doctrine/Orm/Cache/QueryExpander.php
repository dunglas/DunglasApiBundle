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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Cache;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\AbstractQuery;

/**
 * Class QueryExpander.
 *
 * Expands Query settings with Second Level Cache and Result Cache settings from attributes.
 *
 * @author st-it <33101537+st-it@users.noreply.github.com>
 */
class QueryExpander
{
    public const DOCTRINE_CACHE_CONFIG_ATTR = 'doctrine_cache';
    public const CACHEABLE_ATTR = 'cacheable';
    public const CACHE_HINT_ATTR = 'cache_hint';
    public const CACHE_MODE_ATTR = 'cache_mode';
    public const USE_RESULT_CACHE_ATTR = 'use_result_cache';

    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    public function expand(string $resourceClass, AbstractQuery $query)
    {
        $cacheConfig = $this->getDoctrineCacheConfig($resourceClass);
        if (null !== $cacheConfig) {
            $this->processCacheable($cacheConfig, $query);
            $this->processCacheHint($cacheConfig, $query);
            $this->processCacheMode($cacheConfig, $query);
            $this->processResultCache($cacheConfig, $query);
        }
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    private function getDoctrineCacheConfig(string $resourceClass): ?array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $config = $resourceMetadata->getAttribute(self::DOCTRINE_CACHE_CONFIG_ATTR, null);

        return \is_array($config) ? $config : null;
    }

    private function processCacheable(array $cacheConfig, AbstractQuery $query)
    {
        if (false !== ($cacheConfig[self::CACHEABLE_ATTR] ?? false)) {
            $query->setCacheable(true);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function processCacheHint(array $cacheConfig, AbstractQuery $query)
    {
        if (null !== $cacheHint = $cacheConfig[self::CACHE_HINT_ATTR] ?? null) {
            if (!\is_array($cacheHint)) {
                throw new RuntimeException(sprintf('Attribute value %s should be an array', self::CACHE_HINT_ATTR));
            }
            foreach ($cacheHint as $name => $value) {
                $query->setHint($name, $value);
            }
        }
    }

    private function processCacheMode(array $cacheConfig, AbstractQuery $query)
    {
        if (null !== $cacheMode = $cacheConfig[self::CACHE_MODE_ATTR] ?? null) {
            $query->setCacheMode($cacheMode);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function processResultCache(array $cacheConfig, AbstractQuery $query)
    {
        if (null !== $useResultCache = $cacheConfig[self::USE_RESULT_CACHE_ATTR] ?? null) {
            if (!\is_array($useResultCache)) {
                throw new RuntimeException(sprintf('Attribute value %s should be an array', self::USE_RESULT_CACHE_ATTR));
            }
            if (empty($useResultCache) || 3 < \count($useResultCache)) {
                throw new RuntimeException(sprintf('Attribute %s should at least contain one item for use. Other options are lifetime and and result cache id', self::USE_RESULT_CACHE_ATTR));
            }
            $query->useResultCache(...$useResultCache);
        }
    }
}
