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

namespace ApiPlatform\Core\GraphQl\Type;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Interface implemented to build a GraphQL type.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface TypeBuilderInterface
{
    /**
     * Gets the object type of the given resource.
     *
     * @return ObjectType|NonNull the object type, possibly wrapped by NonNull
     */
    public function getResourceObjectType(?string $resourceClass, ResourceMetadata $resourceMetadata, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, bool $wrapped, int $depth): GraphQLType;

    /**
     * Get the interface type of a node.
     */
    public function getNodeInterface(): InterfaceType;

    /**
     * Gets the type of a paginated collection of the given resource type.
     */
    public function getResourcePaginatedCollectionType(GraphQLType $resourceType, string $resourceClass, string $operationName): GraphQLType;

    /**
     * Returns true if a type is a collection.
     */
    public function isCollection(Type $type): bool;
}
