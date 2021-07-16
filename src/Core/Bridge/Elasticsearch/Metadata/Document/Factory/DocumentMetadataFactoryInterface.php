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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;

/**
 * Creates a document metadata value object.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
interface DocumentMetadataFactoryInterface
{
    /**
     * Creates document metadata.
     *
     * @throws IndexNotFoundException
     */
    public function create(string $resourceClass): DocumentMetadata;
}
