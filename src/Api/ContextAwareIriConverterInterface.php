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
use ApiPlatform\Core\Exception\RuntimeException;

/**
 * Converts item and resources to IRI and vice versa.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ContextAwareIriConverterInterface extends IriConverterInterface
{
    /**
     * Gets the IRI associated with the given item.
     *
     * @param object         $item
     * @param array|int|null $referenceType TODO: rename this to $context
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getIriFromItem($item, $referenceType = null): string;
}
