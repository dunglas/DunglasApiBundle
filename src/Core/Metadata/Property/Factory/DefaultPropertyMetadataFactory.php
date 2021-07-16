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
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;

/**
 * Populates defaults values of the ressource properties using the default PHP values of properties.
 */
final class DefaultPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $decorated;

    public function __construct(PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        if (null === $this->decorated) {
            $propertyMetadata = new PropertyMetadata();
        } else {
            try {
                $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                $propertyMetadata = new PropertyMetadata();
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $propertyMetadata;
        }

        $defaultProperties = $reflectionClass->getDefaultProperties();

        if (!\array_key_exists($property, $defaultProperties) || null === ($defaultProperty = $defaultProperties[$property])) {
            return $propertyMetadata;
        }

        return $propertyMetadata->withDefault($defaultProperty);
    }
}
