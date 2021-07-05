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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Resource metadata provider for file configured factories tests.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
abstract class FileConfigurationMetadataFactoryProvider extends TestCase
{
    public function resourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();

        $metadata = [
            'shortName' => 'thedummyshortname',
            'description' => 'Dummy resource',
            'itemOperations' => [
                'my_op_name' => ['method' => 'GET'],
                'my_other_op_name' => ['method' => 'POST'],
            ],
            'collectionOperations' => [
                'my_collection_op' => ['method' => 'POST', 'path' => 'the/collection/path', 'status' => '201'],
            ],
            'subresourceOperations' => [
                'my_collection_subresource' => ['path' => 'the/subresource/path'],
            ],
            'graphql' => [
                'query' => [
                    'normalization_context' => [
                        AbstractNormalizer::GROUPS => ['graphql'],
                    ],
                ],
            ],
            'iri' => 'someirischema',
            'attributes' => [
                'normalization_context' => [
                    AbstractNormalizer::GROUPS => ['default'],
                ],
                'denormalization_context' => [
                    AbstractNormalizer::GROUPS => ['default'],
                ],
                'hydra_context' => [
                    '@type' => 'hydra:Operation',
                    '@hydra:title' => 'File config Dummy',
                ],
                'stateless' => true,
            ],
        ];

        foreach (['shortName', 'description', 'itemOperations', 'collectionOperations', 'subresourceOperations', 'graphql', 'iri', 'attributes'] as $property) {
            $wither = 'with'.ucfirst($property);
            $resourceMetadata = $resourceMetadata->{$wither}($metadata[$property]);
        }

        return [[$resourceMetadata]];
    }

    public function optionalResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withItemOperations(['my_op_name' => ['method' => 'POST']]);

        return [[$resourceMetadata]];
    }

    public function noCollectionOperationsResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withItemOperations(['my_op_name' => ['method' => 'POST']]);
        $resourceMetadata = $resourceMetadata->withCollectionOperations([]);

        return [[$resourceMetadata]];
    }

    public function noItemOperationsResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withCollectionOperations(['my_op_name' => ['method' => 'POST']]);
        $resourceMetadata = $resourceMetadata->withItemOperations([]);

        return [[$resourceMetadata]];
    }

    public function legacyOperationsResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withItemOperations([
            'my_op_name' => ['method' => 'POST'],
            'my_other_op_name' => ['method' => 'GET'],
        ]);
        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'my_op_name' => ['method' => 'POST'],
        ]);

        return [[$resourceMetadata]];
    }

    public function overrideOperationsResourceMetadataProvider()
    {
        $resourceMetadata = $this->resourceMetadataProvider()[0][0];

        $resourceMetadata = $resourceMetadata->withDescription('New description');

        $resourceMetadata = $resourceMetadata->withItemOperations([
            'my_op_name' => ['method' => 'GET', 'path' => 'the/path'],
            'my_other_op_name' => ['method' => 'POST'],
        ]);

        $resourceMetadata = $resourceMetadata->withCollectionOperations([
            'my_collection_op' => ['method' => 'POST', 'status' => '200'],
        ]);

        return [[$resourceMetadata]];
    }
}
