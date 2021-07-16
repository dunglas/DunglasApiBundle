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

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Exception\InvalidResourceException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Util\Reflection;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\PropertyInfo\Type;

/**
 * Adds subresources to the properties metadata from {@see ApiResource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AnnotationSubresourceMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $reader;
    private $decorated;

    public function __construct(Reader $reader, PropertyMetadataFactoryInterface $decorated)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $propertyMetadata;
        }

        if ($reflectionClass->hasProperty($property)) {
            $reflectionProperty = $reflectionClass->getProperty($property);
            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionProperty->getAttributes(ApiSubresource::class)) {
                return $this->updateMetadata($attributes[0]->newInstance(), $propertyMetadata, $resourceClass, $property);
            }

            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, ApiSubresource::class);
            if ($annotation instanceof ApiSubresource) {
                return $this->updateMetadata($annotation, $propertyMetadata, $resourceClass, $property);
            }
        }

        foreach (array_merge(Reflection::ACCESSOR_PREFIXES, Reflection::MUTATOR_PREFIXES) as $prefix) {
            $methodName = $prefix.ucfirst($property);
            if (!$reflectionClass->hasMethod($methodName)) {
                continue;
            }

            $reflectionMethod = $reflectionClass->getMethod($methodName);
            if (!$reflectionMethod->isPublic()) {
                continue;
            }

            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionMethod->getAttributes(ApiSubresource::class)) {
                return $this->updateMetadata($attributes[0]->newInstance(), $propertyMetadata, $resourceClass, $property);
            }

            $annotation = $this->reader->getMethodAnnotation($reflectionMethod, ApiSubresource::class);
            if ($annotation instanceof ApiSubresource) {
                return $this->updateMetadata($annotation, $propertyMetadata, $resourceClass, $property);
            }
        }

        return $propertyMetadata;
    }

    private function updateMetadata(ApiSubresource $annotation, PropertyMetadata $propertyMetadata, string $originResourceClass, string $propertyName): PropertyMetadata
    {
        $type = $propertyMetadata->getType();
        if (null === $type) {
            throw new InvalidResourceException(sprintf('Property "%s" on resource "%s" is declared as a subresource, but its type could not be determined.', $propertyName, $originResourceClass));
        }
        $isCollection = $type->isCollection();

        if (
            $isCollection &&
            $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()
        ) {
            $resourceClass = $collectionValueType->getClassName();
        } else {
            $resourceClass = $type->getClassName();
        }

        $maxDepth = $annotation->maxDepth;
        // @ApiSubresource is on the class identifier (/collection/{id}/subcollection/{subcollectionId})
        if (null === $resourceClass) {
            $resourceClass = $originResourceClass;
            $isCollection = false;
        }

        return $propertyMetadata->withSubresource(new SubresourceMetadata($resourceClass, $isCollection, $maxDepth));
    }
}
