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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Parses and creates IRIs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class IriHelper
{
    private function __construct()
    {
    }

    /**
     * Parses and standardizes the request IRI.
     *
     * @throws InvalidArgumentException
     */
    public static function parseIri(string $iri, string $pageParameterName): array
    {
        $parts = parse_url($iri);
        if (false === $parts) {
            throw new InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $iri));
        }

        $parameters = [];
        if (isset($parts['query'])) {
            $parameters = RequestParser::parseRequestParams($parts['query']);

            // Remove existing page parameter
            unset($parameters[$pageParameterName]);
        }

        return ['parts' => $parts, 'parameters' => $parameters];
    }

    /**
     * Gets a collection IRI for the given parameters.
     *
     * @param float $page
     */
    public static function createIri(array $parts, array $parameters, string $pageParameterName = null, float $page = null, $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH): string
    {
        if (null !== $page && null !== $pageParameterName) {
            $parameters[$pageParameterName] = $page;
        }

        if (\is_bool($urlGenerationStrategy)) {
            @trigger_error(sprintf('Passing a bool as 5th parameter to "%s::createIri()" is deprecated since API Platform 2.6. Pass an "%s" constant (int) instead.', __CLASS__, UrlGeneratorInterface::class), \E_USER_DEPRECATED);
            $urlGenerationStrategy = $urlGenerationStrategy ? UrlGeneratorInterface::ABS_URL : UrlGeneratorInterface::ABS_PATH;
        }

        $query = http_build_query($parameters, '', '&', \PHP_QUERY_RFC3986);
        $parts['query'] = preg_replace('/%5B\d+%5D/', '%5B%5D', $query);

        $url = '';
        if ((UrlGeneratorInterface::ABS_URL === $urlGenerationStrategy || UrlGeneratorInterface::NET_PATH === $urlGenerationStrategy) && isset($parts['host'])) {
            if (isset($parts['scheme'])) {
                $scheme = $parts['scheme'];
            } elseif (isset($parts['port']) && 443 === $parts['port']) {
                $scheme = 'https';
            } else {
                $scheme = 'http';
            }
            $url .= UrlGeneratorInterface::NET_PATH === $urlGenerationStrategy ? '//' : "$scheme://";

            if (isset($parts['user'])) {
                $url .= $parts['user'];

                if (isset($parts['pass'])) {
                    $url .= ':'.$parts['pass'];
                }

                $url .= '@';
            }

            $url .= $parts['host'];

            if (isset($parts['port'])) {
                $url .= ':'.$parts['port'];
            }
        }

        $url .= $parts['path'];

        if ('' !== $parts['query']) {
            $url .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }
}
