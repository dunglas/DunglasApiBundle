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

namespace ApiPlatform\Core\GraphQl\Resolver\Factory;

use ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface;
use ApiPlatform\Core\GraphQl\Subscription\SubscriptionManagerInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use ApiPlatform\Core\Util\CloneTrait;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Creates a function resolving a GraphQL subscription of an item.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ItemSubscriptionResolverFactory implements ResolverFactoryInterface
{
    use ClassInfoTrait;
    use CloneTrait;

    private $readStage;
    private $securityStage;
    private $serializeStage;
    private $resourceMetadataFactory;
    private $subscriptionManager;
    private $mercureSubscriptionIriGenerator;

    public function __construct(ReadStageInterface $readStage, SecurityStageInterface $securityStage, SerializeStageInterface $serializeStage, ResourceMetadataFactoryInterface $resourceMetadataFactory, SubscriptionManagerInterface $subscriptionManager, ?MercureSubscriptionIriGeneratorInterface $mercureSubscriptionIriGenerator)
    {
        $this->readStage = $readStage;
        $this->securityStage = $securityStage;
        $this->serializeStage = $serializeStage;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->subscriptionManager = $subscriptionManager;
        $this->mercureSubscriptionIriGenerator = $mercureSubscriptionIriGenerator;
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?string $operationName = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operationName) {
            if (null === $resourceClass || null === $operationName) {
                return null;
            }

            $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

            $item = ($this->readStage)($resourceClass, $rootClass, $operationName, $resolverContext);
            if (null !== $item && !\is_object($item)) {
                throw new \LogicException('Item from read stage should be a nullable object.');
            }
            ($this->securityStage)($resourceClass, $operationName, $resolverContext + [
                'extra_variables' => [
                    'object' => $item,
                ],
            ]);

            $result = ($this->serializeStage)($item, $resourceClass, $operationName, $resolverContext);

            $subscriptionId = $this->subscriptionManager->retrieveSubscriptionId($resolverContext, $result);

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($subscriptionId && ($mercure = $resourceMetadata->getAttribute('mercure', false))) {
                if (!$this->mercureSubscriptionIriGenerator) {
                    throw new \LogicException('Cannot use Mercure for subscriptions when MercureBundle is not installed. Try running "composer require mercure".');
                }

                $hub = \is_array($mercure) ? ($mercure['hub'] ?? null) : null;
                $result['mercureUrl'] = $this->mercureSubscriptionIriGenerator->generateMercureUrl($subscriptionId, $hub);
            }

            return $result;
        };
    }
}
