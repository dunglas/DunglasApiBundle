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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Api;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\NonUniqueIdentifierException;

/**
 * Extracts identifier for a given resource.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
interface IdentifierExtractorInterface
{
    /**
     * Finds identifier from a resource class.
     *
     * @throws NonUniqueIdentifierException
     */
    public function getIdentifierFromResourceClass(string $resourceClass): string;
}
