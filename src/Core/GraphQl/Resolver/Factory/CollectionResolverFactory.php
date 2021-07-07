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

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\CloneTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates a function retrieving a collection to resolve a GraphQL query or a field returned by a mutation.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class CollectionResolverFactory implements ResolverFactoryInterface
{
    use CloneTrait;

    private $readStage;
    private $securityStage;
    private $securityPostDenormalizeStage;
    private $serializeStage;
    private $queryResolverLocator;
    private $requestStack;
    private $resourceMetadataFactory;

    public function __construct(ReadStageInterface $readStage, SecurityStageInterface $securityStage, SecurityPostDenormalizeStageInterface $securityPostDenormalizeStage, SerializeStageInterface $serializeStage, ContainerInterface $queryResolverLocator, ResourceMetadataFactoryInterface $resourceMetadataFactory, RequestStack $requestStack = null)
    {
        $this->readStage = $readStage;
        $this->securityStage = $securityStage;
        $this->securityPostDenormalizeStage = $securityPostDenormalizeStage;
        $this->serializeStage = $serializeStage;
        $this->queryResolverLocator = $queryResolverLocator;
        $this->requestStack = $requestStack;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?string $operationName = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operationName) {
            // If authorization has failed for a relation field (e.g. via ApiProperty security), the field is not present in the source: null can be returned directly to ensure the collection isn't in the response.
            if (null === $resourceClass || null === $rootClass || (null !== $source && !\array_key_exists($info->fieldName, $source))) {
                return null;
            }

            if ($this->requestStack && null !== $request = $this->requestStack->getCurrentRequest()) {
                $request->attributes->set(
                    '_graphql_collections_args',
                    [$resourceClass => $args] + $request->attributes->get('_graphql_collections_args', [])
                );
            }

            $operationName = $operationName ?? 'collection_query';
            $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => false];

            $collection = ($this->readStage)($resourceClass, $rootClass, $operationName, $resolverContext);
            if (!is_iterable($collection)) {
                throw new \LogicException('Collection from read stage should be iterable.');
            }

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $queryResolverId = $resourceMetadata->getGraphqlAttribute($operationName, 'collection_query');
            if (null !== $queryResolverId) {
                /** @var QueryCollectionResolverInterface $queryResolver */
                $queryResolver = $this->queryResolverLocator->get($queryResolverId);
                $collection = $queryResolver($collection, $resolverContext);
            }

            // Only perform security stage on the top-level query
            if (null === $source) {
                ($this->securityStage)($resourceClass, $operationName, $resolverContext + [
                    'extra_variables' => [
                        'object' => $collection,
                    ],
                ]);
                ($this->securityPostDenormalizeStage)($resourceClass, $operationName, $resolverContext + [
                    'extra_variables' => [
                        'object' => $collection,
                        'previous_object' => $this->clone($collection),
                    ],
                ]);
            }

            return ($this->serializeStage)($collection, $resourceClass, $operationName, $resolverContext);
        };
    }
}
