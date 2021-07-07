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

namespace ApiPlatform\Core\Metadata\Resource;

use ApiPlatform\Core\Api\OperationType;

/**
 * Resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ResourceMetadata
{
    private $shortName;
    private $description;
    private $iri;
    private $itemOperations;
    private $collectionOperations;
    private $subresourceOperations;
    private $graphql;
    private $attributes;

    public function __construct(string $shortName = null, string $description = null, string $iri = null, array $itemOperations = null, array $collectionOperations = null, array $attributes = null, array $subresourceOperations = null, array $graphql = null)
    {
        $this->shortName = $shortName;
        $this->description = $description;
        $this->iri = $iri;
        $this->itemOperations = $itemOperations;
        $this->collectionOperations = $collectionOperations;
        $this->subresourceOperations = $subresourceOperations;
        $this->graphql = $graphql;
        $this->attributes = $attributes;
    }

    /**
     * Gets the short name.
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * Returns a new instance with the given short name.
     */
    public function withShortName(string $shortName): self
    {
        $metadata = clone $this;
        $metadata->shortName = $shortName;

        return $metadata;
    }

    /**
     * Gets the description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns a new instance with the given description.
     */
    public function withDescription(string $description): self
    {
        $metadata = clone $this;
        $metadata->description = $description;

        return $metadata;
    }

    /**
     * Gets the associated IRI.
     */
    public function getIri(): ?string
    {
        return $this->iri;
    }

    /**
     * Returns a new instance with the given IRI.
     */
    public function withIri(string $iri): self
    {
        $metadata = clone $this;
        $metadata->iri = $iri;

        return $metadata;
    }

    /**
     * Gets item operations.
     */
    public function getItemOperations(): ?array
    {
        return $this->itemOperations;
    }

    /**
     * Returns a new instance with the given item operations.
     */
    public function withItemOperations(array $itemOperations): self
    {
        $metadata = clone $this;
        $metadata->itemOperations = $itemOperations;

        return $metadata;
    }

    /**
     * Gets collection operations.
     */
    public function getCollectionOperations(): ?array
    {
        return $this->collectionOperations;
    }

    /**
     * Returns a new instance with the given collection operations.
     */
    public function withCollectionOperations(array $collectionOperations): self
    {
        $metadata = clone $this;
        $metadata->collectionOperations = $collectionOperations;

        return $metadata;
    }

    /**
     * Gets subresource operations.
     */
    public function getSubresourceOperations(): ?array
    {
        return $this->subresourceOperations;
    }

    /**
     * Returns a new instance with the given subresource operations.
     */
    public function withSubresourceOperations(array $subresourceOperations): self
    {
        $metadata = clone $this;
        $metadata->subresourceOperations = $subresourceOperations;

        return $metadata;
    }

    /**
     * Gets a collection operation attribute, optionally fallback to a resource attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getCollectionOperationAttribute(?string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        return $this->findOperationAttribute($this->collectionOperations, $operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets an item operation attribute, optionally fallback to a resource attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getItemOperationAttribute(?string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        return $this->findOperationAttribute($this->itemOperations, $operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets a subresource operation attribute, optionally fallback to a resource attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getSubresourceOperationAttribute(?string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        return $this->findOperationAttribute($this->subresourceOperations, $operationName, $key, $defaultValue, $resourceFallback);
    }

    public function getGraphqlAttribute(string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        if (isset($this->graphql[$operationName][$key])) {
            return $this->graphql[$operationName][$key];
        }

        if ($resourceFallback && isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return $defaultValue;
    }

    /**
     * Gets the first available operation attribute according to the following order: collection, item, subresource, optionally fallback to a default value.
     *
     * @param mixed|null $defaultValue
     */
    public function getOperationAttribute(array $attributes, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        if (isset($attributes['collection_operation_name'])) {
            return $this->getCollectionOperationAttribute($attributes['collection_operation_name'], $key, $defaultValue, $resourceFallback);
        }

        if (isset($attributes['item_operation_name'])) {
            return $this->getItemOperationAttribute($attributes['item_operation_name'], $key, $defaultValue, $resourceFallback);
        }

        if (isset($attributes['subresource_operation_name'])) {
            return $this->getSubresourceOperationAttribute($attributes['subresource_operation_name'], $key, $defaultValue, $resourceFallback);
        }

        if ($resourceFallback && isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return $defaultValue;
    }

    /**
     * Gets an attribute for a given operation type and operation name.
     *
     * @param mixed|null $defaultValue
     */
    public function getTypedOperationAttribute(string $operationType, string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        switch ($operationType) {
            case OperationType::COLLECTION:
                return $this->getCollectionOperationAttribute($operationName, $key, $defaultValue, $resourceFallback);
            case OperationType::ITEM:
                return $this->getItemOperationAttribute($operationName, $key, $defaultValue, $resourceFallback);
            default:
                return $this->getSubresourceOperationAttribute($operationName, $key, $defaultValue, $resourceFallback);
        }
    }

    /**
     * Gets attributes.
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * Gets an attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getAttribute(string $key, $defaultValue = null)
    {
        return $this->attributes[$key] ?? $defaultValue;
    }

    /**
     * Returns a new instance with the given attribute.
     */
    public function withAttributes(array $attributes): self
    {
        $metadata = clone $this;
        $metadata->attributes = $attributes;

        return $metadata;
    }

    /**
     * Gets options of for the GraphQL query.
     */
    public function getGraphql(): ?array
    {
        return $this->graphql;
    }

    /**
     * Returns a new instance with the given GraphQL options.
     */
    public function withGraphql(array $graphql): self
    {
        $metadata = clone $this;
        $metadata->graphql = $graphql;

        return $metadata;
    }

    /**
     * Gets an operation attribute, optionally fallback to a resource attribute.
     *
     * @param mixed|null $defaultValue
     */
    private function findOperationAttribute(?array $operations, ?string $operationName, string $key, $defaultValue, bool $resourceFallback)
    {
        if (null !== $operationName && isset($operations[$operationName][$key])) {
            return $operations[$operationName][$key];
        }

        if ($resourceFallback && isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return $defaultValue;
    }
}
