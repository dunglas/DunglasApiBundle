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

namespace ApiPlatform\Core\Util;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

/**
 * Retrieves information about a resource class.
 *
 * @internal
 */
trait ResourceClassInfoTrait
{
    use ClassInfoTrait;

    /**
     * @var ResourceClassResolverInterface|null
     */
    private $resourceClassResolver;

    /**
     * @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface|null
     */
    private $resourceMetadataFactory;

    /**
     * Gets the resource class of the given object.
     *
     * @param object $object
     * @param bool   $strict If true, object class is expected to be a resource class
     *
     * @return string|null The resource class, or null if object class is not a resource class
     */
    private function getResourceClass($object, bool $strict = false): ?string
    {
        $objectClass = $this->getObjectClass($object);

        if (null === $this->resourceClassResolver) {
            return $objectClass;
        }

        if (!$strict && !$this->resourceClassResolver->isResourceClass($objectClass)) {
            return null;
        }

        return $this->resourceClassResolver->getResourceClass($object);
    }

    private function isResourceClass(string $class): bool
    {
        if ($this->resourceClassResolver instanceof ResourceClassResolverInterface) {
            return $this->resourceClassResolver->isResourceClass($class);
        }

        if (!$this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface || !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            // assume that it's a resource class
            return true;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            return \count($this->resourceMetadataFactory->create($class)) > 0 ? true : false;
        }

        // TODO: 3.0 remove
        try {
            $this->resourceMetadataFactory->create($class);
        } catch (ResourceClassNotFoundException $e) {
            return false;
        }

        return true;
    }
}
