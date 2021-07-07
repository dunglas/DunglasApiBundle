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
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Item data provider for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $client;
    private $documentMetadataFactory;
    private $identifierExtractor;
    private $denormalizer;
    private $resourceMetadataFactory;

    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, IdentifierExtractorInterface $identifierExtractor, DenormalizerInterface $denormalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;
        $this->identifierExtractor = $identifierExtractor;
        $this->denormalizer = $denormalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            if (false === $resourceMetadata->getItemOperationAttribute($operationName, 'elasticsearch', true, true)) {
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
    public function getItem(string $resourceClass, $id, ?string $operationName = null, array $context = [])
    {
        if (\is_array($id)) {
            $id = $id[$this->identifierExtractor->getIdentifierFromResourceClass($resourceClass)];
        }

        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);

        try {
            $document = $this->client->get([
                'index' => $documentMetadata->getIndex(),
                'type' => $documentMetadata->getType(),
                'id' => (string) $id,
            ]);
        } catch (Missing404Exception $e) {
            return null;
        }

        $item = $this->denormalizer->denormalize($document, $resourceClass, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
        if (!\is_object($item) && null !== $item) {
            throw new \UnexpectedValueException('Expected item to be an object or null.');
        }

        return $item;
    }
}
