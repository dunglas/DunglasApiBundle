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
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Purges Varnish.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class VarnishPurger implements PurgerInterface
{
    private $clients;

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(array $clients)
    {
        $this->clients = $clients;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris)
    {
        if (!$iris) {
            return;
        }

        // Create the regex to purge all tags in just one request
        $parts = array_map(function ($iri) {
            return sprintf('(^|\,)%s($|\,)', preg_quote($iri));
        }, $iris);

        $regex = \count($parts) > 1 ? sprintf('(%s)', implode(')|(', $parts)) : array_shift($parts);

        foreach ($this->clients as $client) {
            $client->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => $regex]]);
        }
    }
}
