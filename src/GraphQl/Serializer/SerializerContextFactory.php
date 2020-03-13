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

namespace ApiPlatform\Core\GraphQl\Serializer;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Creates the context used by the Symfony Serializer.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SerializerContextFactory implements SerializerContextFactoryInterface
{
    private $resourceMetadataFactory;
    private $nameConverter;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ?NameConverterInterface $nameConverter)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->nameConverter = $nameConverter;
    }

    public function create(?string $resourceClass, string $operationName, bool $normalization, array $resolverContext): array
    {
        $resourceMetadata = $resourceClass ? $this->resourceMetadataFactory->create($resourceClass) : null;

        $context = [
            'resource_class' => $resourceClass,
            'graphql_operation_name' => $operationName,
        ];

        if ($normalization) {
            $context['attributes'] = $this->fieldsToAttributes($resourceMetadata, $resolverContext);
        }

        if (isset($resolverContext['fields'])) {
            $context['no_resolver_data'] = true;
        }

        if ($resourceMetadata) {
            $context['input'] = $resourceMetadata->getGraphqlAttribute($operationName, 'input', null, true);
            $context['output'] = $resourceMetadata->getGraphqlAttribute($operationName, 'output', null, true);

            $key = $normalization ? 'normalization_context' : 'denormalization_context';
            $context = array_merge($resourceMetadata->getGraphqlAttribute($operationName, $key, [], true), $context);
        }

        return $context;
    }

    /**
     * Retrieves fields, recursively replaces the "_id" key (the raw id) by "id" (the name of the property expected by the Serializer) and flattens edge and node structures (pagination).
     */
    private function fieldsToAttributes(?ResourceMetadata $resourceMetadata, array $context): array
    {
        if (isset($context['fields'])) {
            $fields = $context['fields'];
        } else {
            /** @var ResolveInfo $info */
            $info = $context['info'];
            $fields = $info->getFieldSelection(PHP_INT_MAX);
        }

        $attributes = $this->replaceIdKeys($fields['edges']['node'] ?? $fields);

        if ($context['is_mutation'] || $context['is_subscription']) {
            if (!$resourceMetadata) {
                throw new \LogicException('ResourceMetadata should always exist for a mutation or a subscription.');
            }

            $wrapFieldName = lcfirst($resourceMetadata->getShortName());

            return $attributes[$wrapFieldName] ?? [];
        }

        return $attributes;
    }

    private function replaceIdKeys(array $fields): array
    {
        $denormalizedFields = [];

        foreach ($fields as $key => $value) {
            if ('_id' === $key) {
                $denormalizedFields['id'] = $fields['_id'];

                continue;
            }

            $denormalizedFields[$this->denormalizePropertyName((string) $key)] = \is_array($fields[$key]) ? $this->replaceIdKeys($fields[$key]) : $value;
        }

        return $denormalizedFields;
    }

    private function denormalizePropertyName(string $property): string
    {
        return null !== $this->nameConverter ? $this->nameConverter->denormalize($property) : $property;
    }
}
