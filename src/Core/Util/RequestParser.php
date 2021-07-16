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

namespace ApiPlatform\Core\Util;

use Symfony\Component\HttpFoundation\Request;

/**
 * Utility functions for working with Symfony's HttpFoundation request.
 *
 * @internal
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class RequestParser
{
    private function __construct()
    {
    }

    /**
     * Gets a fixed request.
     */
    public static function parseAndDuplicateRequest(Request $request): Request
    {
        $query = self::parseRequestParams(self::getQueryString($request) ?? '');
        $body = self::parseRequestParams($request->getContent());

        return $request->duplicate($query, $body);
    }

    /**
     * Parses request parameters from the specified source.
     *
     * @author Rok Kralj
     *
     * @see https://stackoverflow.com/a/18209799/1529493
     */
    public static function parseRequestParams(string $source): array
    {
        // '[' is urlencoded ('%5B') in the input, but we must urldecode it in order
        // to find it when replacing names with the regexp below.
        $source = str_replace('%5B', '[', $source);

        $source = preg_replace_callback(
            '/(^|(?<=&))[^=[&]+/',
            static function ($key) {
                return bin2hex(urldecode($key[0]));
            },
            $source
        );

        // parse_str urldecodes both keys and values in resulting array.
        parse_str($source, $params);

        return array_combine(array_map('hex2bin', array_keys($params)), $params);
    }

    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     */
    public static function getQueryString(Request $request): ?string
    {
        $qs = $request->server->get('QUERY_STRING', '');
        if ('' === $qs) {
            return null;
        }

        $parts = [];

        foreach (explode('&', $qs) as $param) {
            if ('' === $param || '=' === $param[0]) {
                // Ignore useless delimiters, e.g. "x=y&".
                // Also ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be retrieved anyway.
                // PHP also does not include them when building _GET.
                continue;
            }

            $keyValuePair = explode('=', $param, 2);

            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in enctype application/x-www-form-urlencoded).
            // PHP also converts "+" to spaces when filling the global _GET or when using the function parse_str. This is why we use urldecode and then normalize to
            // RFC 3986 with rawurlencode.
            $parts[] = isset($keyValuePair[1]) ?
                rawurlencode(urldecode($keyValuePair[0])).'='.rawurlencode(urldecode($keyValuePair[1])) :
                rawurlencode(urldecode($keyValuePair[0]));
        }

        return implode('&', $parts);
    }
}
