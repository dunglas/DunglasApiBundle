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

namespace ApiPlatform\State;

/**
 * Retrieves data from a persistence layer.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
interface ProviderInterface
{
    /**
     * Provides data.
     *
     * @return object|array|null
     */
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []);

    /**
     * Whether this state provider supports the class/identifier tuple.
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool;
}
