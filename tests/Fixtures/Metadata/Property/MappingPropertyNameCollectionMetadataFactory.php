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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;

/**
 * A fixture property name collection metadata factory which allows developers to set up
 * a mapping to return $metadata by self::getKey($class, $options = []).
 *
 * USE FOR UNIT TEST ONLY
 *
 * Example Usage:
 *
 * """
 * $factory = new MappingPropertyNameCollectionMetadataFactory($metadataByClass = [
 *     MappingPropertyNameCollectionMetadataFactory::getKey(DummyEntity::class) => new PropertyNameCollection(...),
 * ]);
 *
 * $metadata = $factory->create(DummyEntity::class); // The property name collection metadata is returned
 * """
 *
 * @author Torrey Tsui <torreytsui@gmail.com>
 */
class MappingPropertyNameCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    /** @var array */
    private $metadataByKey;

    public static function getKey(string $resourceClass, ...$options): string
    {
        return sprintf('%s [%s]', $resourceClass, implode(', ', $options));
    }

    public static function from(): self
    {
        return new static([]);
    }

    public function __construct(array $metadataByKey)
    {
        $this->metadataByKey = $metadataByKey;
    }

    public function withMetadata(PropertyNameCollection $metadata, string $resourceClass, ...$options): self
    {
        $clone = clone $this;
        $clone->metadataByKey[static::getKey($resourceClass, ...$options)] = $metadata;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        if ($metadata = $this->metadataByKey[static::getKey($resourceClass, ...$options)] ?? null) {
            return $metadata;
        }

        throw new \Exception(sprintf('Property name collection metadata for class "%s" is not found in the mapping. Please configure it.', $resourceClass));
    }
}
