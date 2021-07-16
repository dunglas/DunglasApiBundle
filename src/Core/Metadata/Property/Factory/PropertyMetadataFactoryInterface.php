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
 * Creates a property metadata value object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface PropertyMetadataFactoryInterface
{
    /**
     * Creates a property metadata.
     *
     * @throws PropertyNotFoundException
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata;
}
