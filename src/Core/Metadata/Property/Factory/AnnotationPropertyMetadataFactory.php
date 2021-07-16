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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Util\Reflection;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a property metadata from {@see ApiProperty} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $reader;
    private $decorated;

    public function __construct(Reader $reader = null, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $parentPropertyMetadata = null;
        if ($this->decorated) {
            try {
                $parentPropertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($reflectionClass->hasProperty($property)) {
            $annotation = null;
            $reflectionProperty = $reflectionClass->getProperty($property);
            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionProperty->getAttributes(ApiProperty::class)) {
                $annotation = $attributes[0]->newInstance();
            } elseif (null !== $this->reader) {
                $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, ApiProperty::class);
            }

            if ($annotation instanceof ApiProperty) {
                return $this->createMetadata($annotation, $parentPropertyMetadata);
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

            $annotation = null;
            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionMethod->getAttributes(ApiProperty::class)) {
                $annotation = $attributes[0]->newInstance();
            } elseif (null !== $this->reader) {
                $annotation = $this->reader->getMethodAnnotation($reflectionMethod, ApiProperty::class);
            }

            if ($annotation instanceof ApiProperty) {
                return $this->createMetadata($annotation, $parentPropertyMetadata);
            }
        }

        return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound(?PropertyMetadata $parentPropertyMetadata, string $resourceClass, string $property): PropertyMetadata
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of class "%s" not found.', $property, $resourceClass));
    }

    private function createMetadata(ApiProperty $annotation, PropertyMetadata $parentPropertyMetadata = null): PropertyMetadata
    {
        if (null === $parentPropertyMetadata) {
            return new PropertyMetadata(
                null,
                $annotation->description,
                $annotation->readable,
                $annotation->writable,
                $annotation->readableLink,
                $annotation->writableLink,
                $annotation->required,
                $annotation->identifier,
                $annotation->iri,
                null,
                $annotation->attributes,
                null,
                null,
                $annotation->default,
                $annotation->example
            );
        }

        $propertyMetadata = $parentPropertyMetadata;
        foreach ([['get', 'description'], ['is', 'readable'], ['is', 'writable'], ['is', 'readableLink'], ['is', 'writableLink'], ['is', 'required'], ['get', 'iri'], ['is', 'identifier'], ['get', 'attributes'], ['get', 'default'], ['get', 'example']] as $property) {
            if (null !== $value = $annotation->{$property[1]}) {
                $propertyMetadata = $this->createWith($propertyMetadata, $property, $value);
            }
        }

        return $propertyMetadata;
    }

    private function createWith(PropertyMetadata $propertyMetadata, array $property, $value): PropertyMetadata
    {
        $wither = 'with'.ucfirst($property[1]);

        return $propertyMetadata->{$wither}($value);
    }
}
