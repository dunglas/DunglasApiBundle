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

use ApiPlatform\Core\Exception\RuntimeException;

/**
 * Extracts identifiers for a given Resource according to the retrieved Metadata.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface IdentifiersExtractorInterface
{
    /**
     * Finds identifiers from a Resource class.
     */
    public function getIdentifiersFromResourceClass(string $resourceClass): array;

    /**
     * Finds identifiers from an Item (object).
     *
     * @param object $item
     *
     * @throws RuntimeException
     */
    public function getIdentifiersFromItem($item): array;
}
