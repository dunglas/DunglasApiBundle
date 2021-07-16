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
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    private $resourceMetadataFactory;

    public function __construct($resourceMetadataFactory = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('The use of %s is deprecated, use %s instead.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        // TODO: 3.0 change the condition to remove the ResourceMetadataFactorym only used to skip null values
        if (
            $this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface
            && isset($attributes['operation_name']) && isset($attributes['operation'])
        ) {
            $context = $normalization ? $attributes['operation']->getNormalizationContext() : $attributes['operation']->getDenormalizationContext();
            $context['operation_name'] = $attributes['operation_name'];
            $context['operation'] = $attributes['operation'];
            $context['resource_class'] = $attributes['resource_class'];
            // TODO: 3.0 becomes true by default
            $context['skip_null_values'] = $context['skip_null_values'] ?? $this->shouldSkipNullValues($attributes['resource_class'], $attributes['operation_name']);
            // TODO: remove in 3.0, operation type will not exist anymore
            $context['operation_type'] = $attributes['operation']->isCollection() ? OperationType::ITEM : OperationType::COLLECTION;
            $context['iri_only'] = $context['iri_only'] ?? false;
            $context['request_uri'] = $request->getRequestUri();
            $context['uri'] = $request->getUri();

            $context['identifiers_values'] = [];
            foreach (array_keys($attributes['operation']->getIdentifiers()) as $parameterName) {
                $context['identifiers_values'][$parameterName] = $request->attributes->get($parameterName);
            }

            if (!$normalization) {
                if (!isset($context['api_allow_update'])) {
                    $context['api_allow_update'] = \in_array($method = $request->getMethod(), ['PUT', 'PATCH'], true);

                    if ($context['api_allow_update'] && 'PATCH' === $method) {
                        $context['deep_object_to_populate'] = $context['deep_object_to_populate'] ?? true;
                    }
                }

                if ('csv' === $request->getContentType()) {
                    $context[CsvEncoder::AS_COLLECTION_KEY] = false;
                }
            }

            return $context;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $key = $normalization ? 'normalization_context' : 'denormalization_context';
        if (isset($attributes['collection_operation_name'])) {
            $operationKey = 'collection_operation_name';
            $operationType = OperationType::COLLECTION;
        } elseif (isset($attributes['item_operation_name'])) {
            $operationKey = 'item_operation_name';
            $operationType = OperationType::ITEM;
        } else {
            $operationKey = 'subresource_operation_name';
            $operationType = OperationType::SUBRESOURCE;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            $operation = $resourceMetadata->getOperation($attributes['operation_name']);
            $context = $normalization ? $operation->getNormalizationContext() : $operation->getDenormalizationContext();
            $context['operation_name'] = $attributes['operation_name'];
            $context['operation'] = $operation;
            // TODO: use $context['operation'] instead?
            $context['iri_only'] = $operation->getNormalizationContext()['iri_only'] ?? false;
            $context['input'] = $operation->getInput();
            $context['output'] = $operation->getOutput();
            $context['types'] = $operation->getTypes();
        } else {
            $context = $resourceMetadata->getTypedOperationAttribute($operationType, $attributes[$operationKey], $key, [], true);
            $context['operation_type'] = $operationType;
            $context[$operationKey] = $attributes[$operationKey];
            $context['iri_only'] = $resourceMetadata->getAttribute('normalization_context')['iri_only'] ?? false;
            $context['input'] = $resourceMetadata->getTypedOperationAttribute($operationType, $attributes[$operationKey], 'input', null, true);
            $context['output'] = $resourceMetadata->getTypedOperationAttribute($operationType, $attributes[$operationKey], 'output', null, true);
        }

        if (!$normalization) {
            if (!isset($context['api_allow_update'])) {
                $context['api_allow_update'] = \in_array($method = $request->getMethod(), ['PUT', 'PATCH'], true);

                if ($context['api_allow_update'] && 'PATCH' === $method) {
                    $context['deep_object_to_populate'] = $context['deep_object_to_populate'] ?? true;
                }
            }

            if ('csv' === $request->getContentType()) {
                $context[CsvEncoder::AS_COLLECTION_KEY] = false;
            }
        }

        $context['resource_class'] = $attributes['resource_class'];
        $context['request_uri'] = $request->getRequestUri();
        $context['uri'] = $request->getUri();

        if (isset($attributes['subresource_context'])) {
            $context['subresource_identifiers'] = [];

            foreach ($attributes['subresource_context']['identifiers'] as $parameterName => [$resourceClass]) {
                if (!isset($context['subresource_resources'][$resourceClass])) {
                    $context['subresource_resources'][$resourceClass] = [];
                }

                $context['subresource_identifiers'][$parameterName] = $context['subresource_resources'][$resourceClass][$parameterName] = $request->attributes->get($parameterName);
            }
        }

        if (isset($attributes['subresource_property'])) {
            $context['subresource_property'] = $attributes['subresource_property'];
            $context['subresource_resource_class'] = $attributes['subresource_resource_class'] ?? null;
        }

        unset($context[DocumentationNormalizer::SWAGGER_DEFINITION_NAME]);

        if (isset($context['skip_null_values'])) {
            return $context;
        }

        // TODO: We should always use `skip_null_values` but changing this would be a BC break, for now use it only when `merge-patch+json` is activated on a Resource
        if (!$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            foreach ($resourceMetadata->getItemOperations() as $operation) {
                if ('PATCH' === ($operation['method'] ?? '') && \in_array('application/merge-patch+json', $operation['input_formats']['json'] ?? [], true)) {
                    $context['skip_null_values'] = true;

                    break;
                }
            }
        } else {
            $context['skip_null_values'] = $this->shouldSkipNullValues($attributes['resource_class'], $attributes['operation_name']);
        }

        return $context;
    }

    /**
     * TODO: remove in 3.0, this will have no impact and skip_null_values will be default, no more resourceMetadataFactory call in this class.
     */
    private function shouldSkipNullValues(string $class, string $operationName): bool
    {
        if (!$this->resourceMetadataFactory) {
            return true;
        }

        $collection = $this->resourceMetadataFactory->create($class);
        foreach ($collection as $metadata) {
            foreach ($metadata->getOperations() as $operation) {
                if ('PATCH' === ($operation->getMethod() ?? '') && \in_array('application/merge-patch+json', $operation->getInputFormats()['json'] ?? [], true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
