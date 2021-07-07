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

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use ApiPlatform\Core\Util\CloneTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;

/**
 * Creates a function retrieving an item to resolve a GraphQL query.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ItemResolverFactory implements ResolverFactoryInterface
{
    use ClassInfoTrait;
    use CloneTrait;

    private $readStage;
    private $securityStage;
    private $securityPostDenormalizeStage;
    private $serializeStage;
    private $queryResolverLocator;
    private $resourceMetadataFactory;

    public function __construct(ReadStageInterface $readStage, SecurityStageInterface $securityStage, SecurityPostDenormalizeStageInterface $securityPostDenormalizeStage, SerializeStageInterface $serializeStage, ContainerInterface $queryResolverLocator, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->readStage = $readStage;
        $this->securityStage = $securityStage;
        $this->securityPostDenormalizeStage = $securityPostDenormalizeStage;
        $this->serializeStage = $serializeStage;
        $this->queryResolverLocator = $queryResolverLocator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?string $operationName = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operationName) {
            // Data already fetched and normalized (field or nested resource)
            if ($source && \array_key_exists($info->fieldName, $source)) {
                return $source[$info->fieldName];
            }

            $operationName = $operationName ?? 'item_query';
            $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false];

            $item = ($this->readStage)($resourceClass, $rootClass, $operationName, $resolverContext);
            if (null !== $item && !\is_object($item)) {
                throw new \LogicException('Item from read stage should be a nullable object.');
            }

            $resourceClass = $this->getResourceClass($item, $resourceClass);
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $queryResolverId = $resourceMetadata->getGraphqlAttribute($operationName, 'item_query');
            if (null !== $queryResolverId) {
                /** @var QueryItemResolverInterface $queryResolver */
                $queryResolver = $this->queryResolverLocator->get($queryResolverId);
                $item = $queryResolver($item, $resolverContext);
                $resourceClass = $this->getResourceClass($item, $resourceClass, sprintf('Custom query resolver "%s"', $queryResolverId).' has to return an item of class %s but returned an item of class %s.');
            }

            ($this->securityStage)($resourceClass, $operationName, $resolverContext + [
                'extra_variables' => [
                    'object' => $item,
                ],
            ]);
            ($this->securityPostDenormalizeStage)($resourceClass, $operationName, $resolverContext + [
                'extra_variables' => [
                    'object' => $item,
                    'previous_object' => $this->clone($item),
                ],
            ]);

            return ($this->serializeStage)($item, $resourceClass, $operationName, $resolverContext);
        };
    }

    /**
     * @param object|null $item
     *
     * @throws \UnexpectedValueException
     */
    private function getResourceClass($item, ?string $resourceClass, string $errorMessage = 'Resolver only handles items of class %s but retrieved item is of class %s.'): string
    {
        if (null === $item) {
            if (null === $resourceClass) {
                throw new \UnexpectedValueException('Resource class cannot be determined.');
            }

            return $resourceClass;
        }

        $itemClass = $this->getObjectClass($item);

        if (null === $resourceClass) {
            return $itemClass;
        }

        if ($resourceClass !== $itemClass) {
            throw new \UnexpectedValueException(sprintf($errorMessage, (new \ReflectionClass($resourceClass))->getShortName(), (new \ReflectionClass($itemClass))->getShortName()));
        }

        return $resourceClass;
    }
}
