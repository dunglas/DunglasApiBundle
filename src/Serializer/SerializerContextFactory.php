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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * {@inheritdoc}
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SerializerContextFactory implements SerializerContextFactoryInterface
{
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $operationName, bool $normalization, array $context): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $key = $normalization ? 'normalization_context' : 'denormalization_context';
        if (isset($context['collection_operation_name'])) {
            $operationNameKey = 'collection_operation_name';
            $operationType = OperationType::COLLECTION;
        } elseif (isset($context['item_operation_name'])) {
            $operationNameKey = 'item_operation_name';
            $operationType = OperationType::ITEM;
        } elseif (isset($context['resource_operation_name'])) {
            $operationNameKey = 'resource_operation_name';
            $operationType = OperationType::RESOURCE;
        } else {
            $operationNameKey = 'subresource_operation_name';
            $operationType = OperationType::SUBRESOURCE;
        }

        $serializerContext = $resourceMetadata->getTypedOperationAttribute($operationType, $context[$operationNameKey], $key, [], true);
        $serializerContext['operation_type'] = $operationType;
        $serializerContext[$operationNameKey] = $context[$operationNameKey];

        if (!$normalization) {
            if (!isset($serializerContext['api_allow_update'])) {
                $serializerContext['api_allow_update'] = \in_array($context['request_method'] ?? null, ['PUT', 'PATCH'], true);
            }

            if ('csv' === ($context['request_content_type'] ?? null)) {
                $serializerContext[CsvEncoder::AS_COLLECTION_KEY] = false;
            }
        }

        $serializerContext['resource_class'] = $resourceClass;
        $serializerContext['input'] = $resourceMetadata->getTypedOperationAttribute($operationType, $context[$operationNameKey], 'input', null, true);
        $serializerContext['output'] = $resourceMetadata->getTypedOperationAttribute($operationType, $context[$operationNameKey], 'output', null, true);
        $serializerContext['request_uri'] = $context['request_request_uri'] ?? null;
        $serializerContext['uri'] = $context['request_uri'] ?? null;

        if (isset($context['subresource_context'])) {
            $serializerContext['subresource_identifiers'] = [];

            foreach ($context['subresource_context']['identifiers'] as $key => [$id, $subResourceClass]) {
                if (!isset($serializerContext['subresource_resources'][$subResourceClass])) {
                    $serializerContext['subresource_resources'][$subResourceClass] = [];
                }

                $serializerContext['subresource_identifiers'][$id] = $serializerContext['subresource_resources'][$subResourceClass][$id] = $context['request_attributes'][$id] ?? null;
            }
        }

        if (isset($context['subresource_property'])) {
            $serializerContext['subresource_property'] = $context['subresource_property'];
            $serializerContext['subresource_resource_class'] = $context['subresource_resource_class'] ?? null;
        }

        if (isset($serializerContext['skip_null_values'])) {
            return $serializerContext;
        }

        foreach ($resourceMetadata->getItemOperations() ?? [] as $operation) {
            if ('PATCH' === ($operation['method'] ?? '') && \in_array('application/merge-patch+json', $operation['input_formats']['json'] ?? [], true)) {
                $serializerContext['skip_null_values'] = true;

                break;
            }
        }

        return $serializerContext;
    }
}
