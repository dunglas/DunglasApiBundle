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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a resource metadata from {@see ApiResource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $reader;
    private $decorated;
    private $defaults;

    public function __construct(Reader $reader = null, ResourceMetadataFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
        $this->defaults = $defaults + ['attributes' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $parentResourceMetadata = null;
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionClass->getAttributes(ApiResource::class)) {
            return $this->createMetadata($attributes[0]->newInstance(), $parentResourceMetadata);
        }

        if (null === $this->reader) {
            $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        $resourceAnnotation = $this->reader->getClassAnnotation($reflectionClass, ApiResource::class);
        if (!$resourceAnnotation instanceof ApiResource) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        return $this->createMetadata($resourceAnnotation, $parentResourceMetadata);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(?ResourceMetadata $parentPropertyMetadata, string $resourceClass): ResourceMetadata
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    private function createMetadata(ApiResource $annotation, ResourceMetadata $parentResourceMetadata = null): ResourceMetadata
    {
        $attributes = null;
        if (null !== $annotation->attributes || [] !== $this->defaults['attributes']) {
            $attributes = (array) $annotation->attributes;
            foreach ($this->defaults['attributes'] as $key => $value) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $value;
                }
            }
        }

        if (!$parentResourceMetadata) {
            return new ResourceMetadata(
                $annotation->shortName,
                $annotation->description ?? $this->defaults['description'] ?? null,
                $annotation->iri ?? $this->defaults['iri'] ?? null,
                $annotation->itemOperations ?? $this->defaults['item_operations'] ?? null,
                $annotation->collectionOperations ?? $this->defaults['collection_operations'] ?? null,
                $attributes,
                $annotation->subresourceOperations,
                $annotation->graphql ?? $this->defaults['graphql'] ?? null
            );
        }

        $resourceMetadata = $parentResourceMetadata;
        foreach (['shortName', 'description', 'iri', 'itemOperations', 'collectionOperations', 'subresourceOperations', 'graphql', 'attributes'] as $property) {
            $resourceMetadata = $this->createWith($resourceMetadata, $property, $annotation->{$property});
        }

        return $resourceMetadata;
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function createWith(ResourceMetadata $resourceMetadata, string $property, $value): ResourceMetadata
    {
        $upperProperty = ucfirst($property);
        $getter = "get$upperProperty";

        if (null !== $resourceMetadata->{$getter}()) {
            return $resourceMetadata;
        }

        if (null === $value) {
            return $resourceMetadata;
        }

        $wither = "with$upperProperty";

        return $resourceMetadata->{$wither}($value);
    }
}
