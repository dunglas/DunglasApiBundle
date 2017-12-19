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
interface IriConverterInterface
{
    /**
     * Retrieves an item from its IRI.
     *
     * @param string $iri
     * @param array  $context
     *
     * @throws InvalidArgumentException
     *
     * @return object
     */
    public function getItemFromIri(string $iri, array $context = []);

    /**
     * Gets the IRI associated with the given item.
     *
     * @param object $item
     * @param int    $referenceType
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return string
     */
    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;

    /**
     * Gets the IRI associated with the given resource collection.
     *
     * @param string $resourceClass
     * @param int    $referenceType
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;

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
     * Gets the context's IRI associated with the given resource.
     *
     * @param string $shortName
     * @param int    $referenceType
     *
     * @return string
     */
    public function getContextIriFromShortName(string $shortName, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;

    /**
     * Gets the IRI associated with the API doc.
     *
     * @param int $referenceType
     *
     * @return string
     */
    public function getApiDocIri(int $referenceType = UrlGeneratorInterface::ABS_PATH): string;

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
