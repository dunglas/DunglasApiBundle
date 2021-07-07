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

/**
 * Interface implemented to build GraphQL fields.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface FieldsBuilderInterface
{
    /**
     * Gets the fields of a node for a query.
     */
    public function getNodeQueryFields(): array;

    /**
     * Gets the item query fields of the schema.
     */
    public function getItemQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $queryName, array $configuration): array;

    /**
     * Gets the collection query fields of the schema.
     */
    public function getCollectionQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $queryName, array $configuration): array;

    /**
     * Gets the mutation fields of the schema.
     */
    public function getMutationFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $mutationName): array;

    /**
     * Gets the subscription fields of the schema.
     */
    public function getSubscriptionFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $subscriptionName): array;

    /**
     * Gets the fields of the type of the given resource.
     */
    public function getResourceObjectTypeFields(?string $resourceClass, ResourceMetadata $resourceMetadata, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, int $depth, ?array $ioMetadata): array;

    /**
     * Resolve the args of a resource by resolving its types.
     */
    public function resolveResourceArgs(array $args, string $operationName, string $shortName): array;
}
