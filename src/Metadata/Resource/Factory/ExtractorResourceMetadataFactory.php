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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Extractor\ExtractorInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Creates resource's metadata using an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ExtractorResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $extractor;
    private $decorated;
    private $defaults;
    private $resources = ['description', 'iri', 'itemOperations', 'collectionOperations', 'graphql'];

    public function __construct(ExtractorInterface $extractor, ResourceMetadataFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->extractor = $extractor;
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

        if (!(class_exists($resourceClass) || interface_exists($resourceClass)) || !$resource = $this->extractor->getResources()[$resourceClass] ?? false) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        foreach ($this->resources as $availableResource) {
            $resource[$availableResource] =
                $resource[$availableResource] ?? $this->defaults[strtolower(preg_replace('/(?<!^)[A-Z]+|(?<!^|\d)[\d]+/', '_$0', $availableResource))] ?? null;
        }

        if (null !== $resource['attributes'] || [] !== $this->defaults['attributes']) {
            $resource['attributes'] = (array) $resource['attributes'];
            foreach ($this->defaults['attributes'] as $key => $value) {
                if (!isset($resource['attributes'][$key])) {
                    $resource['attributes'][$key] = $value;
                }
            }
        }

        return $this->update($parentResourceMetadata ?: new ResourceMetadata(), $resource);
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

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function update(ResourceMetadata $resourceMetadata, array $metadata): ResourceMetadata
    {
        foreach (['shortName', 'description', 'iri', 'itemOperations', 'collectionOperations', 'subresourceOperations', 'graphql', 'attributes'] as $propertyName) {
            $propertyValue = $this->resolveResourceMetadataPropertyValue($propertyName, $resourceMetadata, $metadata);
            if (null !== $propertyValue) {
                $resourceMetadata = $resourceMetadata->{'with' . ucfirst($propertyName)}($propertyValue);
            }
        }

        return $resourceMetadata;
    }

    /** @return mixed */
    private function resolveResourceMetadataPropertyValue(
        string $propertyName,
        ResourceMetadata $parentResourceMetadata,
        array $childResourceMetadata
    ) {
        $parentPropertyValue = $parentResourceMetadata->{'get' . ucfirst($propertyName)}();

        $childPropertyValue = $childResourceMetadata[$propertyName];
        if (null === $childPropertyValue) {
            return $parentPropertyValue;
        }

        if (null === $parentPropertyValue) {
            return $childPropertyValue;
        }

        if (is_array($parentPropertyValue)) {
            if (!is_array($childPropertyValue)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid child property value type for property "%s", expected array',
                    $propertyName,
                ));
            }

            return $this->replaceConfigs($parentPropertyValue, $childPropertyValue);
        }

        return $childPropertyValue;
    }

    private function replaceConfigs(...$configs): array
    {
        $resultingConfig = [];

        foreach ($configs as $config) {
            foreach ($config as $newKey => $newValue) {
                $unsetNewKey = false;
                if (is_string($newKey) && 1 === preg_match('/^(.*[^ ]) +\\(unset\\)$/', $newKey, $matches)) {
                    [, $newKey] = $matches;
                    $unsetNewKey = true;
                }

                if ($unsetNewKey) {
                    unset($resultingConfig[$newKey]);

                    if (null === $newValue) {
                        continue;
                    }
                }

                if (is_integer($newKey)) {
                    $resultingConfig[] = $newValue;
                } else {
                    $resultingConfig[$newKey] = $newValue;
                }
            }
        }

        return $resultingConfig;
    }
}
