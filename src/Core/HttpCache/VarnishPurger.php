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
 * Purges Varnish.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class VarnishPurger implements PurgerInterface
{
    private const DEFAULT_VARNISH_MAX_HEADER_LENGTH = 8000;
    private const REGEXP_PATTERN = '(%s)($|\,)';

    private $clients;
    private $maxHeaderLength;

    /**
     * @param ClientInterface[] $clients
     */
    public function __construct(array $clients, int $maxHeaderLength = self::DEFAULT_VARNISH_MAX_HEADER_LENGTH)
    {
        $this->clients = $clients;
        $this->maxHeaderLength = $maxHeaderLength - mb_strlen(self::REGEXP_PATTERN) + 2; // 2 for %s
    }

    /**
     * Calculate how many tags fit into the header.
     *
     * This assumes that the tags are separated by one character.
     *
     * From https://github.com/FriendsOfSymfony/FOSHttpCache/blob/2.8.0/src/ProxyClient/HttpProxyClient.php#L137
     *
     * @param string[] $escapedTags
     * @param string   $glue        The concatenation string to use
     *
     * @return int Number of tags per tag invalidation request
     */
    private function determineTagsPerHeader(array $escapedTags, string $glue): int
    {
        if (mb_strlen(implode($glue, $escapedTags)) < $this->maxHeaderLength) {
            return \count($escapedTags);
        }
        /*
         * estimate the amount of tags to invalidate by dividing the max
         * header length by the largest tag (minus the glue length)
         */
        $tagsize = max(array_map('mb_strlen', $escapedTags));
        $gluesize = \strlen($glue);

        return (int) floor(($this->maxHeaderLength + $gluesize) / ($tagsize + $gluesize)) ?: 1;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris)
    {
        if (!$iris) {
            return;
        }

        $chunkSize = $this->determineTagsPerHeader($iris, '|');

        $irisChunks = array_chunk($iris, $chunkSize);
        foreach ($irisChunks as $irisChunk) {
            $this->purgeRequest($irisChunk);
        }
    }

    private function purgeRequest(array $iris)
    {
        // Create the regex to purge all tags in just one request
        $parts = array_map(static function ($iri) {
            // here we should remove the prefix as it's not discriminent and cost a lot to compute
            return preg_quote($iri);
        }, $iris);

        foreach ($this->chunkRegexParts($parts) as $regex) {
            $regex = sprintf(self::REGEXP_PATTERN, $regex);
            $this->banRegex($regex);
        }
    }

    private function banRegex(string $regex): void
    {
        foreach ($this->clients as $client) {
            $client->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => $regex]]);
        }
    }

    private function chunkRegexParts(array $parts): iterable
    {
        if (1 === \count($parts)) {
            yield $parts[0];

            return;
        }

        $concatenatedParts = implode("\n", $parts);

        if (\strlen($concatenatedParts) <= $this->maxHeaderLength) {
            yield str_replace("\n", '|', $concatenatedParts);

            return;
        }

        $lastSeparator = strrpos(substr($concatenatedParts, 0, $this->maxHeaderLength + 1), "\n");

        $chunk = substr($concatenatedParts, 0, $lastSeparator);

        yield str_replace("\n", '|', $chunk);

        $nextParts = \array_slice($parts, substr_count($chunk, "\n") + 1);

        yield from $this->chunkRegexParts($nextParts);
    }
}
