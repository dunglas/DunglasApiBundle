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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches document metadata.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class CachedDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    private const CACHE_KEY_PREFIX = 'index_metadata';

    private $cacheItemPool;
    private $decorated;
    private $localCache = [];

    public function __construct(CacheItemPoolInterface $cacheItemPool, DocumentMetadataFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): DocumentMetadata
    {
        if (isset($this->localCache[$resourceClass])) {
            return $this->handleNotFound($this->localCache[$resourceClass], $resourceClass);
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem(self::CACHE_KEY_PREFIX.md5($resourceClass));
        } catch (CacheException $e) {
            return $this->handleNotFound($this->localCache[$resourceClass] = $this->decorated->create($resourceClass), $resourceClass);
        }

        if ($cacheItem->isHit()) {
            return $this->handleNotFound($this->localCache[$resourceClass] = $cacheItem->get(), $resourceClass);
        }

        $documentMetadata = $this->decorated->create($resourceClass);

        $cacheItem->set($documentMetadata);
        $this->cacheItemPool->save($cacheItem);

        return $this->handleNotFound($this->localCache[$resourceClass] = $documentMetadata, $resourceClass);
    }

    /**
     * @throws IndexNotFoundException
     */
    private function handleNotFound(DocumentMetadata $documentMetadata, string $resourceClass): DocumentMetadata
    {
        if (null === $documentMetadata->getIndex()) {
            throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
        }

        return $documentMetadata;
    }
}
