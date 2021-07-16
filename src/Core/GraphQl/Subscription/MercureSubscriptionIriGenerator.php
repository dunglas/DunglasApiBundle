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

namespace ApiPlatform\Core\GraphQl\Subscription;

use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Routing\RequestContext;

/**
 * Generates Mercure-related IRIs from a subscription ID.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MercureSubscriptionIriGenerator implements MercureSubscriptionIriGeneratorInterface
{
    private $requestContext;
    private $registry;

    /**
     * @param HubRegistry|string $registry
     */
    public function __construct(RequestContext $requestContext, $registry)
    {
        $this->requestContext = $requestContext;
        $this->registry = $registry;
    }

    public function generateTopicIri(string $subscriptionId): string
    {
        if ('' === $scheme = $this->requestContext->getScheme()) {
            $scheme = 'https';
        }
        if ('' === $host = $this->requestContext->getHost()) {
            $host = 'api-platform.com';
        }

        return "$scheme://$host/subscriptions/$subscriptionId";
    }

    public function generateMercureUrl(string $subscriptionId, ?string $hub = null): string
    {
        if (!$this->registry instanceof HubRegistry) {
            return sprintf('%s?topic=%s', $this->registry, $this->generateTopicIri($subscriptionId));
        }

        return sprintf('%s?topic=%s', $this->registry->getHub($hub)->getUrl(), $this->generateTopicIri($subscriptionId));
    }
}
