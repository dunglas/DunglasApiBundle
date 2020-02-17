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
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

final class DirectoryResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $defaults;
    private $decorated;

    public function __construct(
        array $defaults = [],
        ResourceMetadataFactoryInterface $decorated = null
    ) {
        $this->defaults = $defaults + ['attributes' => []];
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadata
    {
        $parentResourceMetadata = null;
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $e) {
            }
        }
        $resourceMetadata = $parentResourceMetadata ?: new ResourceMetadata();

        if (null !== $resourceMetadata->getShortname()) {
            return $resourceMetadata;
        }

        return $resourceMetadata->withShortname((new \ReflectionClass($resourceClass))->getShortName());
    }
}
