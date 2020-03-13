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
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Manipulates the property factory options.
 *
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait PropertyFactoryOptionsTrait
{
    /**
     * Gets the options for a property factory.
     */
    private function getPropertyFactoryOptions(string $resourceClass, bool $splitDeserialization = false): array
    {
        $serializationContext = $this->getSerializationContext($resourceClass, true);
        $deserializationContext = $this->getSerializationContext($resourceClass, false);
        $serializationGroups = (array) ($serializationContext[AbstractNormalizer::GROUPS] ?? []);
        $deserializationGroups = (array) ($deserializationContext[AbstractNormalizer::GROUPS] ?? []);

        if (!$splitDeserialization) {
            $serializationGroups = array_unique(array_merge($serializationGroups, $deserializationGroups));
            $deserializationGroups = [];
        }

        $options = [];
        if ($serializationGroups) {
            $options['serializer_groups'] = $serializationGroups;
        }
        if ($deserializationGroups) {
            $options['deserializer_groups'] = $deserializationGroups;
        }

        return $options;
    }

    /**
     * Get the serialization context using the serializer context builder if available or the resource attributes if not.
     */
    private function getSerializationContext(string $resourceClass, bool $normalization, ?string $operationType = null, ?string $operationName = null): array
    {
        try {
            if ($this->serializerContextFactory) {
                switch ($operationType) {
                    case OperationType::ITEM:
                        $operationKey = 'item_operation_name';
                        break;
                    case OperationType::COLLECTION:
                        $operationKey = 'collection_operation_name';
                        break;
                    case OperationType::SUBRESOURCE:
                        $operationKey = 'subresource_operation_name';
                        break;
                    default:
                        $operationKey = 'resource_operation_name';
                }

                return $this->serializerContextFactory->create($resourceClass, $operationName ?? 'resource', $normalization, [
                    $operationKey => $operationName ?? 'resource',
                ]);
            }

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $attribute = $normalization ? 'normalization_context' : 'denormalization_context';

            if (null === $operationType || null === $operationName) {
                return $resourceMetadata->getAttribute($attribute, []);
            }

            return $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, $attribute, [], true);
        } catch (ResourceClassNotFoundException $exception) {
            return [];
        }
    }
}
