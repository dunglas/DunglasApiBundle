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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

/**
 * Write stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class WriteStage implements WriteStageInterface
{
    private $resourceMetadataFactory;
    private $dataPersister;
    private $serializerContextBuilder;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ContextAwareDataPersisterInterface $dataPersister, SerializerContextBuilderInterface $serializerContextBuilder)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->dataPersister = $dataPersister;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($data, string $resourceClass, string $operationName, array $context)
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (null === $data || !$resourceMetadata->getGraphqlAttribute($operationName, 'write', true, true)) {
            return $data;
        }

        $denormalizationContext = $this->serializerContextBuilder->create($resourceClass, $operationName, $context, false);

        if ('delete' === $operationName) {
            $this->dataPersister->remove($data, $denormalizationContext);

            return null;
        }

        $persistResult = $this->dataPersister->persist($data, $denormalizationContext);

        if (!\is_object($persistResult)) {
            @trigger_error(sprintf('Not returning an object from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3.', DataPersisterInterface::class), \E_USER_DEPRECATED);
            $persistResult = null;
        }

        return $persistResult;
    }
}
