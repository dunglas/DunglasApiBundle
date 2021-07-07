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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Guesses which resource is associated with a given object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceClassResolverInterface
{
    /**
     * Guesses the associated resource.
     *
     * @param string $resourceClass The expected resource class
     * @param bool   $strict        If true, value must match the expected resource class
     *
     * @throws InvalidArgumentException
     */
    public function getResourceClass($value, string $resourceClass = null, bool $strict = false): string;

    /**
     * Is the given class a resource class?
     */
    public function isResourceClass(string $type): bool;
}
