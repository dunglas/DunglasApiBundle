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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;

/**
 * Builds the GraphQL schema.
 *
 * @experimental
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaBuilder implements SchemaBuilderInterface
{
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $typesFactory;
    private $typesContainer;
    private $fieldsBuilder;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, TypesFactoryInterface $typesFactory, TypesContainerInterface $typesContainer, FieldsBuilderInterface $fieldsBuilder)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->typesFactory = $typesFactory;
        $this->typesContainer = $typesContainer;
        $this->fieldsBuilder = $fieldsBuilder;
    }

    public function getSchema(): Schema
    {
        $types = $this->typesFactory->getTypes();
        foreach ($types as $typeId => $type) {
            $this->typesContainer->set($typeId, $type);
        }

        $queryFields = ['node' => $this->fieldsBuilder->getNodeQueryFields()];
        $mutationFields = [];
        $subscriptionFields = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            /** @var array<string, mixed> $graphqlConfiguration */
            $graphqlConfiguration = $resourceMetadata->getGraphql() ?? [];
            foreach ($graphqlConfiguration as $operationName => $value) {
                if ('item_query' === $operationName) {
                    $queryFields += $this->fieldsBuilder->getItemQueryFields($resourceClass, $resourceMetadata, $operationName, []);

                    continue;
                }

                if ('collection_query' === $operationName) {
                    $queryFields += $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $resourceMetadata, $operationName, []);

                    continue;
                }

                if ($resourceMetadata->getGraphqlAttribute($operationName, 'item_query')) {
                    $queryFields += $this->fieldsBuilder->getItemQueryFields($resourceClass, $resourceMetadata, $operationName, $value);

                    continue;
                }

                if ($resourceMetadata->getGraphqlAttribute($operationName, 'collection_query')) {
                    $queryFields += $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $resourceMetadata, $operationName, $value);

                    continue;
                }

                if ('update' === $operationName) {
                    $subscriptionFields += $this->fieldsBuilder->getSubscriptionFields($resourceClass, $resourceMetadata, $operationName);
                }

                $mutationFields += $this->fieldsBuilder->getMutationFields($resourceClass, $resourceMetadata, $operationName);
            }
        }

        $schema = [
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $queryFields,
            ]),
            'typeLoader' => function ($name) {
                $type = $this->typesContainer->get($name);

                if ($type instanceof WrappingType) {
                    return $type->getWrappedType(true);
                }

                return $type;
            },
        ];

        if ($mutationFields) {
            $schema['mutation'] = new ObjectType([
                'name' => 'Mutation',
                'fields' => $mutationFields,
            ]);
        }

        if ($subscriptionFields) {
            $schema['subscription'] = new ObjectType([
                'name' => 'Subscription',
                'fields' => $subscriptionFields,
            ]);
        }

        return new Schema($schema);
    }
}
