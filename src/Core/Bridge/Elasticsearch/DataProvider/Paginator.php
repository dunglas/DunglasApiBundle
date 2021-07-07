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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Paginator for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class Paginator implements \IteratorAggregate, PaginatorInterface
{
    private $denormalizer;
    private $documents;
    private $resourceClass;
    private $limit;
    private $offset;
    private $cachedDenormalizedDocuments = [];
    private $denormalizationContext = [];

    public function __construct(DenormalizerInterface $denormalizer, array $documents, string $resourceClass, int $limit, int $offset, array $denormalizationContext = [])
    {
        $this->denormalizer = $denormalizer;
        $this->documents = $documents;
        $this->resourceClass = $resourceClass;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->denormalizationContext = $denormalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return isset($this->documents['hits']['hits']) ? \count($this->documents['hits']['hits']) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->limit) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        // for elastic search version > 7.0.0
        if (\is_array($this->documents['hits']['total'])) {
            return (float) ($this->documents['hits']['total']['value'] ?? 0.);
        }

        // for elastic search old versions
        return (float) ($this->documents['hits']['total'] ?? 0.);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return floor($this->offset / $this->limit) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float) $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        $denormalizationContext = array_merge([AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true], $this->denormalizationContext);

        foreach ($this->documents['hits']['hits'] ?? [] as $document) {
            $cacheKey = isset($document['_index'], $document['_type'], $document['_id']) ? md5("${document['_index']}_${document['_type']}_${document['_id']}") : null;

            if ($cacheKey && \array_key_exists($cacheKey, $this->cachedDenormalizedDocuments)) {
                $object = $this->cachedDenormalizedDocuments[$cacheKey];
            } else {
                $object = $this->denormalizer->denormalize(
                    $document,
                    $this->resourceClass,
                    DocumentNormalizer::FORMAT,
                    $denormalizationContext
                );

                if ($cacheKey) {
                    $this->cachedDenormalizedDocuments[$cacheKey] = $object;
                }
            }

            yield $object;
        }
    }
}
