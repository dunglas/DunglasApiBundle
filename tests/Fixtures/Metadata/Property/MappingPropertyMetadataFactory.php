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

namespace ApiPlatform\Core\Tests\Fixtures\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;

/**
 * A fixture property metadata factory which allows developers to set up
 * a mapping to return $metadata by self::getKey($class, $property, $options = []).
 *
 * USE FOR UNIT TEST ONLY
 *
 * Example Usage:
 *
 * """
 * $factory = new MappingPropertyMetadataFactory($metadataByClass = [
 *     MappingPropertyMetadataFactory::getKey(DummyEntity::class, 'foo') => new PropertyMetadata(...),
 * ]);
 *
 * $metadata = $factory->create(DummyEntity::class, 'foo'); // The property metadata is returned
 * """
 *
 * @author Torrey Tsui <torreytsui@gmail.com>
 */
class MappingPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    /** @var array */
    private $metadataByKey;

    public static function getKey(string $resourceClass, string $property, array $options = []): string
    {
        return sprintf('%s $%s [%s]', $resourceClass, $property, implode(', ', $options));
    }

    public static function from(): self
    {
        return new static([]);
    }

    public function __construct(array $metadataByKey)
    {
        $this->metadataByKey = $metadataByKey;
    }

    public function withMetadata(PropertyMetadata $metadata, string $resourceClass, string $property, ...$options): self
    {
        $clone = clone $this;
        $clone->metadataByKey[static::getKey($resourceClass, $property, ...$options)] = $metadata;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        if ($metadata = $this->metadataByKey[static::getKey($resourceClass, $property, $options)] ?? null) {
            return $metadata;
        }

        throw new \Exception(sprintf('Property metadata for class "%s" is not found in the mapping. Please configure it.', $resourceClass));
    }
}
