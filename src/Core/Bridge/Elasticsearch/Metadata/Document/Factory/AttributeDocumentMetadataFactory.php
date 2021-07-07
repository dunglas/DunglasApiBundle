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

/**
 * Creates document's metadata using the attribute configuration.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class AttributeDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    private $resourceMetadataFactory;
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ?DocumentMetadataFactoryInterface $decorated = null)
    {
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

        $resourceMetadata = null;

        if (!$documentMetadata || null === $documentMetadata->getIndex()) {
            $resourceMetadata = $resourceMetadata ?? $this->resourceMetadataFactory->create($resourceClass);

            if (null !== $index = $resourceMetadata->getAttribute('elasticsearch_index')) {
                $documentMetadata = $documentMetadata ? $documentMetadata->withIndex($index) : new DocumentMetadata($index);
            }
        }

        if (!$documentMetadata || DocumentMetadata::DEFAULT_TYPE === $documentMetadata->getType()) {
            $resourceMetadata = $resourceMetadata ?? $this->resourceMetadataFactory->create($resourceClass);

            if (null !== $type = $resourceMetadata->getAttribute('elasticsearch_type')) {
                $documentMetadata = $documentMetadata ? $documentMetadata->withType($type) : new DocumentMetadata(null, $type);
            }
        }

        if ($documentMetadata) {
            return $documentMetadata;
        }

        throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
    }
}
