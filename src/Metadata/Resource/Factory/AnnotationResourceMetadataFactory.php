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

    public function __construct(Reader $reader, ResourceMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
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
        if (!$parentResourceMetadata) {
            return new ResourceMetadata(
                $annotation->shortName,
                $annotation->description,
                $annotation->iri,
                $annotation->itemOperations,
                $annotation->collectionOperations,
                $annotation->attributes,
                $annotation->subresourceOperations,
                $annotation->graphql
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

        $currentValue = $resourceMetadata->{$getter}();
        if (null !== $currentValue) {
            if (null === $value) {
                $value = $currentValue;
            } elseif (is_array($currentValue)) {
                $value = array_merge_recursive($currentValue, $value);
            }
        }

        if (null === $value) {
            return $resourceMetadata;
        }

        $wither = "with$upperProperty";

        return $resourceMetadata->{$wither}($value);
    }
}
