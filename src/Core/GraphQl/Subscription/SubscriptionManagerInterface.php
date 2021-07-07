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

/**
 * Manages all the queried subscriptions and creates their ID.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface SubscriptionManagerInterface
{
    public function retrieveSubscriptionId(array $context, ?array $result): ?string;

    public function getPushPayloads($object): array;
}
