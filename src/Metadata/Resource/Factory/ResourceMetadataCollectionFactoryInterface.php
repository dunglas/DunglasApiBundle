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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Creates a resource metadata value object.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
interface ResourceMetadataCollectionFactoryInterface
{
    /**
     * Creates a resource metadata.
     *
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass): ResourceMetadataCollection;
}
