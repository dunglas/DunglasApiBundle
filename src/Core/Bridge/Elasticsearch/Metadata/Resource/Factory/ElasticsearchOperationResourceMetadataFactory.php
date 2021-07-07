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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Creates read-only operations for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ElasticsearchOperationResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        if (null === $resourceMetadata->getCollectionOperations()) {
            $resourceMetadata = $resourceMetadata->withCollectionOperations(['get' => ['method' => 'GET']]);
        }

        if (null === $resourceMetadata->getItemOperations()) {
            $resourceMetadata = $resourceMetadata->withItemOperations(['get' => ['method' => 'GET']]);
        }

        return $resourceMetadata;
    }
}
