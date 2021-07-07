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

namespace ApiPlatform\Core\Metadata\Property;

/**
 * Subresource metadata.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class SubresourceMetadata
{
    private $resourceClass;
    private $collection;
    private $maxDepth;

    public function __construct(string $resourceClass, bool $collection = false, int $maxDepth = null)
    {
        $this->resourceClass = $resourceClass;
        $this->collection = $collection;
        $this->maxDepth = $maxDepth;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function withResourceClass($resourceClass): self
    {
        $metadata = clone $this;
        $metadata->resourceClass = $resourceClass;

        return $metadata;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function withCollection(bool $collection): self
    {
        $metadata = clone $this;
        $metadata->collection = $collection;

        return $metadata;
    }

    public function getMaxDepth(): ?int
    {
        return $this->maxDepth;
    }
}
