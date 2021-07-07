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

namespace ApiPlatform\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;

/**
 * Converts item and resources to IRI and vice versa.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface IriConverterInterface
{
    /**
     * Retrieves an item from its IRI.
     *
     * @throws InvalidArgumentException
     * @throws ItemNotFoundException
     *
     * @return object
     */
    public function getItemFromIri(string $iri, array $context = []);

    /**
     * Gets the IRI associated with the given item.
     *
     * @param object $item
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getIriFromItem($item, string $operationName = null, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string;

    /**
     * Gets the IRI associated with the given resource collection.
     *
     * @throws InvalidArgumentException
     */
    public function getIriFromResourceClass(string $resourceClass, string $operationName = null, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string;
}
