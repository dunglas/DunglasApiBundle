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

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Extractor\ExtractorInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use Symfony\Component\PropertyInfo\Type;

/**
 * Creates properties's metadata using an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExtractorPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $extractor;
    private $decorated;

    public function __construct(ExtractorInterface $extractor, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
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

        $isInterface = interface_exists($resourceClass);

        if (
            !property_exists($resourceClass, $property) && !$isInterface ||
            null === ($propertyMetadata = $this->extractor->getResources()[$resourceClass]['properties'][$property] ?? null)
        ) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($parentPropertyMetadata) {
            return $this->update($parentPropertyMetadata, $propertyMetadata);
        }

        return ($metadata = new PropertyMetadata(
            null,
            $propertyMetadata['description'],
            $propertyMetadata['readable'],
            $propertyMetadata['writable'],
            $propertyMetadata['readableLink'],
            $propertyMetadata['writableLink'],
            $propertyMetadata['required'],
            $propertyMetadata['identifier'],
            $propertyMetadata['iri'],
            null,
            $propertyMetadata['attributes']
        ))->withSubresource($this->createSubresourceMetadata($propertyMetadata['subresource'], $metadata));
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound(?PropertyMetadata $parentPropertyMetadata, string $resourceClass, string $property): PropertyMetadata
    {
        if ($parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function update(PropertyMetadata $propertyMetadata, array $metadata): PropertyMetadata
    {
        $metadataAccessors = [
            'description' => 'get',
            'readable' => 'is',
            'writable' => 'is',
            'writableLink' => 'is',
            'readableLink' => 'is',
            'required' => 'is',
            'identifier' => 'is',
            'iri' => 'get',
            'attributes' => 'get',
        ];

        foreach ($metadataAccessors as $metadataKey => $accessorPrefix) {
            if (null === $metadata[$metadataKey]) {
                continue;
            }

            $propertyMetadata = $propertyMetadata->{'with'.ucfirst($metadataKey)}($metadata[$metadataKey]);
        }

        if ($propertyMetadata->hasSubresource()) {
            return $propertyMetadata;
        }

        return $propertyMetadata->withSubresource($this->createSubresourceMetadata($metadata['subresource'], $propertyMetadata));
    }

    /**
     * Creates a SubresourceMetadata.
     *
     * @param bool|array|null  $subresource      the subresource metadata coming from XML or YAML
     * @param PropertyMetadata $propertyMetadata the current property metadata
     */
    private function createSubresourceMetadata($subresource, PropertyMetadata $propertyMetadata): ?SubresourceMetadata
    {
        if (!$subresource) {
            return null;
        }

        $type = $propertyMetadata->getType();
        $maxDepth = \is_array($subresource) ? $subresource['maxDepth'] ?? null : null;

        if (null !== $type) {
            $isCollection = $type->isCollection();
            if (
                $isCollection &&
                $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()
            ) {
                $resourceClass = $collectionValueType->getClassName();
            } else {
                $resourceClass = $type->getClassName();
            }
        } elseif (\is_array($subresource) && isset($subresource['resourceClass'])) {
            $resourceClass = $subresource['resourceClass'];
            $isCollection = $subresource['collection'] ?? true;
        } else {
            return null;
        }

        return new SubresourceMetadata($resourceClass, $isCollection, $maxDepth);
    }
}
