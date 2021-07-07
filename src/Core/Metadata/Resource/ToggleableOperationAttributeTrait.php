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

namespace ApiPlatform\Core\Metadata\Resource;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

/**
 * @internal
 */
trait ToggleableOperationAttributeTrait
{
    /**
     * @var ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface|null
     */
    private $resourceMetadataFactory;

    private function isOperationAttributeDisabled(array $attributes, string $attribute, bool $default = false, bool $resourceFallback = true): bool
    {
        if (null === $this->resourceMetadataFactory || isset($attributes['operation'])) {
            return !($attributes['operation'][$attribute] ?? $attributes[$attribute] ?? !$default);
        }

        // TODO: 3.0 should be removed
        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class'])->getOperation($attributes['operation_name']);

            return !$resourceMetadata->{'get'.ucfirst($attribute)}();
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

        return !((bool) $resourceMetadata->getOperationAttribute($attributes, $attribute, !$default, $resourceFallback));
    }
}
