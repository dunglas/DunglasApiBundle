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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\Inflector;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * Creates document's metadata using indices from the cat APIs.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/cat-indices.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class CatDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    private $client;
    private $resourceMetadataFactory;
    private $decorated;

    public function __construct(Client $client, ResourceMetadataFactoryInterface $resourceMetadataFactory, ?DocumentMetadataFactoryInterface $decorated = null)
    {
        $this->client = $client;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): DocumentMetadata
    {
        $documentMetadata = null;

        if ($this->decorated) {
            try {
                $documentMetadata = $this->decorated->create($resourceClass);
            } catch (IndexNotFoundException $e) {
            }
        }

        if ($documentMetadata && null !== $documentMetadata->getIndex()) {
            return $documentMetadata;
        }

        if (null === $resourceShortName = $this->resourceMetadataFactory->create($resourceClass)->getShortName()) {
            return $this->handleNotFound($documentMetadata, $resourceClass);
        }

        $index = Inflector::tableize($resourceShortName);

        try {
            $this->client->cat()->indices(['index' => $index]);
        } catch (Missing404Exception $e) {
            return $this->handleNotFound($documentMetadata, $resourceClass);
        }

        return ($documentMetadata ?? new DocumentMetadata())->withIndex($index);
    }

    /**
     * @throws IndexNotFoundException
     */
    private function handleNotFound(?DocumentMetadata $documentMetadata, string $resourceClass): DocumentMetadata
    {
        if ($documentMetadata) {
            return $documentMetadata;
        }

        throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
    }
}
