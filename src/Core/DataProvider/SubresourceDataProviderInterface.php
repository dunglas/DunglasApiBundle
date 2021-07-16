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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * Retrieves subresources from a persistence layer.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface SubresourceDataProviderInterface
{
    /**
     * Retrieves a subresource of an item.
     *
     * @param string $resourceClass The root resource class
     * @param array  $identifiers   Identifiers and their values
     * @param array  $context       The context indicates the conjunction between collection properties (identifiers) and their class
     * @param string $operationName
     *
     * @throws ResourceClassNotSupportedException
     *
     * @return iterable|object|null
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null);
}
