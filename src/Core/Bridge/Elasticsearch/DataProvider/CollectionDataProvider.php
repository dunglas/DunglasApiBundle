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

use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\NonUniqueIdentifierException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Elasticsearch\Client;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Collection data provider for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class CollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $client;
    private $documentMetadataFactory;
    private $identifierExtractor;
    private $denormalizer;
    private $pagination;
    private $resourceMetadataFactory;
    private $collectionExtensions;

    /**
     * @param RequestBodySearchCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, IdentifierExtractorInterface $identifierExtractor, DenormalizerInterface $denormalizer, Pagination $pagination, ResourceMetadataFactoryInterface $resourceMetadataFactory, iterable $collectionExtensions = [])
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;
        $this->identifierExtractor = $identifierExtractor;
        $this->denormalizer = $denormalizer;
        $this->pagination = $pagination;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            if (false === $resourceMetadata->getCollectionOperationAttribute($operationName, 'elasticsearch', true, true)) {
                return false;
            }
        } catch (ResourceClassNotFoundException $e) {
            return false;
        }

        try {
            $this->documentMetadataFactory->create($resourceClass);
        } catch (IndexNotFoundException $e) {
            return false;
        }

        try {
            $this->identifierExtractor->getIdentifierFromResourceClass($resourceClass);
        } catch (NonUniqueIdentifierException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = [])
    {
        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);
        $body = [];

        foreach ($this->collectionExtensions as $collectionExtension) {
            $body = $collectionExtension->applyToCollection($body, $resourceClass, $operationName, $context);
        }

        if (!isset($body['query']) && !isset($body['aggs'])) {
            $body['query'] = ['match_all' => new \stdClass()];
        }

        $limit = $body['size'] = $body['size'] ?? $this->pagination->getLimit($resourceClass, $operationName, $context);
        $offset = $body['from'] = $body['from'] ?? $this->pagination->getOffset($resourceClass, $operationName, $context);

        $documents = $this->client->search([
            'index' => $documentMetadata->getIndex(),
            'type' => $documentMetadata->getType(),
            'body' => $body,
        ]);

        return new Paginator(
            $this->denormalizer,
            $documents,
            $resourceClass,
            $limit,
            $offset,
            $context
        );
    }
}
