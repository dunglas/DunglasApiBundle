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
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;

/**
 *
 * @author Mathieu Dewet <dunglas@gmail.com>
 */
interface ItemToIriConverterInterface
{
    /**
     * Retrieves an item from its IRI.
     *
     * @param string $iri
     * @param array  $context
     *
     * @throws InvalidArgumentException
     * @throws ItemNotFoundException
     *
     * @return object
     */
    public function getItemFromIri(string $iri, array $context = []);

    /**
     * Gets the item IRI associated with the given resource.
     *
     * @param string $resourceClass
     * @param array  $identifiers
     * @param int    $referenceType
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getItemIriFromResourceClass(string $resourceClass, array $identifiers, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;

    /**
     * Gets the IRI associated with the given resource subresource.
     *
     * @param string $resourceClass
     * @param array  $identifiers
     * @param int    $referenceType
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getSubresourceIriFromResourceClass(string $resourceClass, array $identifiers, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;
}
