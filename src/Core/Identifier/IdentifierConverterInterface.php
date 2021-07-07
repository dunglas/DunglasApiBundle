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

namespace ApiPlatform\Core\Identifier;

use ApiPlatform\Core\Exception\InvalidIdentifierException;

/**
 * Identifier converter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface IdentifierConverterInterface
{
    /**
     * @internal
     */
    public const HAS_IDENTIFIER_CONVERTER = 'has_identifier_converter';

    /**
     * Takes an array of strings representing identifiers and transform their values to the expected type.
     *
     * @param mixed  $data  Identifier to convert to php values
     * @param string $class The class to which the identifiers belong
     *
     * @throws InvalidIdentifierException
     *
     * @return array Indexed by identifiers properties with their values denormalized
     */
    public function convert($data, string $class): array;
}
