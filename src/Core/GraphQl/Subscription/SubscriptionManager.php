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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use ApiPlatform\Core\Util\SortTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Manages all the queried subscriptions by creating their ID
 * and saving to a cache the information needed to publish updated data.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubscriptionManager implements SubscriptionManagerInterface
{
    use IdentifierTrait;
    use ResourceClassInfoTrait;
    use SortTrait;

    private $subscriptionsCache;
    private $subscriptionIdentifierGenerator;
    private $serializeStage;
    private $iriConverter;

    public function __construct(CacheItemPoolInterface $subscriptionsCache, SubscriptionIdentifierGeneratorInterface $subscriptionIdentifierGenerator, SerializeStageInterface $serializeStage, IriConverterInterface $iriConverter)
    {
        $this->subscriptionsCache = $subscriptionsCache;
        $this->subscriptionIdentifierGenerator = $subscriptionIdentifierGenerator;
        $this->serializeStage = $serializeStage;
        $this->iriConverter = $iriConverter;
    }

    public function retrieveSubscriptionId(array $context, ?array $result): ?string
    {
        /** @var ResolveInfo $info */
        $info = $context['info'];
        $fields = $info->getFieldSelection(\PHP_INT_MAX);
        $this->arrayRecursiveSort($fields, 'ksort');
        $iri = $this->getIdentifierFromContext($context);
        if (null === $iri) {
            return null;
        }
        $subscriptionsCacheItem = $this->subscriptionsCache->getItem($this->encodeIriToCacheKey($iri));
        $subscriptions = [];
        if ($subscriptionsCacheItem->isHit()) {
            $subscriptions = $subscriptionsCacheItem->get();
            foreach ($subscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
                if ($subscriptionFields === $fields) {
                    return $subscriptionId;
                }
            }
        }

        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($fields);
        unset($result['clientSubscriptionId']);
        $subscriptions[] = [$subscriptionId, $fields, $result];
        $subscriptionsCacheItem->set($subscriptions);
        $this->subscriptionsCache->save($subscriptionsCacheItem);

        return $subscriptionId;
    }

    /**
     * @param object $object
     */
    public function getPushPayloads($object): array
    {
        $iri = $this->iriConverter->getIriFromItem($object);
        $subscriptions = $this->getSubscriptionsFromIri($iri);

        $resourceClass = $this->getObjectClass($object);

        $payloads = [];
        foreach ($subscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            $resolverContext = ['fields' => $subscriptionFields, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

            $data = ($this->serializeStage)($object, $resourceClass, 'update', $resolverContext);
            unset($data['clientSubscriptionId']);

            if ($data !== $subscriptionResult) {
                $payloads[] = [$subscriptionId, $data];
            }
        }

        return $payloads;
    }

    /**
     * @return array<array>
     */
    private function getSubscriptionsFromIri(string $iri): array
    {
        $subscriptionsCacheItem = $this->subscriptionsCache->getItem($this->encodeIriToCacheKey($iri));

        if ($subscriptionsCacheItem->isHit()) {
            return $subscriptionsCacheItem->get();
        }

        return [];
    }

    private function encodeIriToCacheKey(string $iri): string
    {
        return str_replace('/', '_', $iri);
    }
}
