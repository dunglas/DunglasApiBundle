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

namespace ApiPlatform\Core\HttpCache;

use GuzzleHttp\ClientInterface;

/**
 * Purges Varnish XKey.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class VarnishXKeyPurger implements PurgerInterface
{
    private const VARNISH_MAX_HEADER_LENGTH = 8000;

    private $clients;
    private $maxHeaderLength;
    private $xkeyGlue;

    /**
     * @param ClientInterface[] $clients
     */
    public function __construct(array $clients, int $maxHeaderLength = self::VARNISH_MAX_HEADER_LENGTH, string $xkeyGlue = ' ')
    {
        $this->clients = $clients;
        $this->maxHeaderLength = $maxHeaderLength;
        $this->xkeyGlue = $xkeyGlue;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris)
    {
        if (!$iris) {
            return;
        }

        $irisChunks = array_chunk($iris, \count($iris));

        foreach ($irisChunks as $irisChunk) {
            $this->purgeIris($irisChunk);
        }
    }

    private function purgeIris(array $iris): void
    {
        foreach ($this->chunkKeys($iris) as $keys) {
            $this->purgeKeys($keys);
        }
    }

    private function purgeKeys(string $keys): void
    {
        foreach ($this->clients as $client) {
            $client->request('PURGE', '', ['headers' => ['xkey' => $keys]]);
        }
    }

    private function chunkKeys(array $keys): iterable
    {
        $concatenatedKeys = implode($this->xkeyGlue, $keys);

        // If all keys fit in the header, we can return them
        if (\strlen($concatenatedKeys) <= $this->maxHeaderLength) {
            yield $concatenatedKeys;

            return;
        }

        $currentHeader = '';

        foreach ($keys as $position => $key) {
            if (\strlen($key) > $this->maxHeaderLength) {
                throw new \Exception(sprintf('IRI "%s" is too long to fit current max header length (currently set to "%s"). You can increase it using the "api_platform.http_cache.invalidation.max_header_length" parameter.', $key, $this->maxHeaderLength));
            }

            $headerCandidate = sprintf('%s%s%s', $currentHeader, $position > 0 ? $this->xkeyGlue : '', $key);

            if (\strlen($headerCandidate) > $this->maxHeaderLength) {
                $nextKeys = \array_slice($keys, $position, \count($keys) - $position);

                yield $currentHeader;
                yield from $this->chunkKeys($nextKeys);

                break;
            }

            // Key can be added to header
            $currentHeader .= sprintf('%s%s', $position > 0 ? $this->xkeyGlue : '', $key);
        }
    }
}
