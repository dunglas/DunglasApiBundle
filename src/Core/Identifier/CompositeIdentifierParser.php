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

/**
 * Normalizes a composite identifier.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class CompositeIdentifierParser
{
    public const COMPOSITE_IDENTIFIER_REGEXP = '/(\w+)=(?<=\w=)(.*?)(?=;\w+=)|(\w+)=([^;]*);?$/';

    private function __construct()
    {
    }

    /*
     * Normalize takes a string and gives back an array of identifiers.
     *
     * For example: foo=0;bar=2 returns ['foo' => 0, 'bar' => 2].
     */
    public static function parse(string $identifier): array
    {
        $matches = [];
        $identifiers = [];
        $num = preg_match_all(self::COMPOSITE_IDENTIFIER_REGEXP, $identifier, $matches, \PREG_SET_ORDER);

        foreach ($matches as $i => $match) {
            if ($i === $num - 1) {
                $identifiers[$match[3]] = $match[4];
                continue;
            }
            $identifiers[$match[1]] = $match[2];
        }

        return $identifiers;
    }

    /**
     * Renders composite identifiers to string using: key=value;key2=value2.
     */
    public static function stringify(array $identifiers): string
    {
        $composite = [];
        foreach ($identifiers as $name => $value) {
            $composite[] = sprintf('%s=%s', $name, $value);
        }

        return implode(';', $composite);
    }
}
