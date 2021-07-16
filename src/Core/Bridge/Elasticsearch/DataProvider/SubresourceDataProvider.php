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
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\NonUniqueIdentifierException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Elasticsearch\Client;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SubresourceDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
{
    private $client;
    private $documentMetadataFactory;
    private $identifierExtractor;
    private $denormalizer;
    private $pagination;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $collectionExtensions;

    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, IdentifierExtractorInterface $identifierExtractor, DenormalizerInterface $denormalizer, Pagination $pagination, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $collectionExtensions = [])
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;
        $this->identifierExtractor = $identifierExtractor;
        $this->denormalizer = $denormalizer;
        $this->pagination = $pagination;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
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

        // Elasticsearch does not support subresources as items (yet)
        if (false === ($context['collection'] ?? false)) {
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
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);
        $body = [];

        foreach ($this->collectionExtensions as $collectionExtension) {
            $body = $collectionExtension->applyToCollection($body, $resourceClass, $operationName, $context);
        }

        // Elasticsearch subresources only works with single identifiers
        $idKey = key($context['identifiers']);
        [$relationClass, $identiferProperty] = $context['identifiers'][$idKey];

        $propertyName = null;

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);
            $type = $propertyMetadata->getType();

            if ($type && $type->getClassName() === $relationClass) {
                $propertyName = $property;
                break;
            }
        }

        if (!$propertyName) {
            throw new RuntimeException('The property has not been found for this relation.');
        }

        $relationQuery = ['match' => [$propertyName.'.'.$identiferProperty => $identifiers[$idKey]]];

        if (!isset($body['query']) && !isset($body['aggs'])) {
            $body['query'] = $relationQuery;
        } else {
            $body['query']['constant_score']['filter']['bool']['must'][] = $relationQuery;
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
