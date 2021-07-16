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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Util;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Field datatypes helpers.
 *
 * @internal
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
trait FieldDatatypeTrait
{
    /**
     * @var PropertyMetadataFactoryInterface
     */
    private $propertyMetadataFactory;

    /**
     * @var ResourceClassResolverInterface
     */
    private $resourceClassResolver;

    /**
     * Is the decomposed given property of the given resource class potentially mapped as a nested field in Elasticsearch?
     */
    private function isNestedField(string $resourceClass, string $property): bool
    {
        return null !== $this->getNestedFieldPath($resourceClass, $property);
    }

    /**
     * Get the nested path to the decomposed given property (e.g.: foo.bar.baz => foo.bar).
     */
    private function getNestedFieldPath(string $resourceClass, string $property): ?string
    {
        $properties = explode('.', $property);
        $currentProperty = array_shift($properties);

        if (!$properties) {
            return null;
        }

        try {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $currentProperty);
        } catch (PropertyNotFoundException $e) {
            return null;
        }

        if (null === $type = $propertyMetadata->getType()) {
            return null;
        }

        if (
            Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()
            && null !== ($nextResourceClass = $type->getClassName())
            && $this->resourceClassResolver->isResourceClass($nextResourceClass)
        ) {
            $nestedPath = $this->getNestedFieldPath($nextResourceClass, implode('.', $properties));

            return null === $nestedPath ? $nestedPath : "$currentProperty.$nestedPath";
        }

        if (
            null !== ($type = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType())
            && Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()
            && null !== ($className = $type->getClassName())
            && $this->resourceClassResolver->isResourceClass($className)
        ) {
            return $currentProperty;
        }

        return null;
    }
}
