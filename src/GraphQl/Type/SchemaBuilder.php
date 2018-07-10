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

use ApiPlatform\Core\Api\EnumInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\GraphQl\Type\Definition\IterableType;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Doctrine\Common\Util\Inflector;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\PropertyInfo\Type;

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
    use ClassInfoTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $collectionResolverFactory;
    private $itemResolver;
    private $itemMutationResolverFactory;
    private $defaultFieldResolver;
    private $filterLocator;
    private $paginationEnabled;
    private $graphqlTypes = [];

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResolverFactoryInterface $collectionResolverFactory, ResolverFactoryInterface $itemMutationResolverFactory, callable $itemResolver, callable $defaultFieldResolver, ContainerInterface $filterLocator = null, bool $paginationEnabled = true)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->collectionResolverFactory = $collectionResolverFactory;
        $this->itemResolver = $itemResolver;
        $this->itemMutationResolverFactory = $itemMutationResolverFactory;
        $this->defaultFieldResolver = $defaultFieldResolver;
        $this->filterLocator = $filterLocator;
        $this->paginationEnabled = $paginationEnabled;
    }

    public function getSchema(): Schema
    {
        $queryFields = ['node' => $this->getNodeQueryField()];
        $mutationFields = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $graphqlConfiguration = $resourceMetadata->getGraphql() ?? [];
            foreach ($graphqlConfiguration as $operationName => $value) {
                if ('query' === $operationName) {
                    $queryFields += $this->getQueryFields($resourceClass, $resourceMetadata);

                    continue;
                }

                $mutationFields[$operationName.$resourceMetadata->getShortName()] = $this->getMutationFields($resourceClass, $resourceMetadata, $operationName);
            }
        }

        return new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $queryFields,
            ]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => $mutationFields,
            ]),
        ]);
    }

    private function getNodeInterface(): InterfaceType
    {
        if (isset($this->graphqlTypes['#node'])) {
            return $this->graphqlTypes['#node'];
        }

        return $this->graphqlTypes['#node'] = new InterfaceType([
            'name' => 'Node',
            'description' => 'A node, according to the Relay specification.',
            'fields' => [
                'id' => [
                    'type' => GraphQLType::nonNull(GraphQLType::id()),
                    'description' => 'The id of this node.',
                ],
            ],
            'resolveType' => function ($value) {
                if (!isset($value[ItemNormalizer::ITEM_KEY])) {
                    return null;
                }

                $resourceClass = $this->getObjectClass(unserialize($value[ItemNormalizer::ITEM_KEY]));

                return $this->graphqlTypes[$resourceClass][null][false] ?? null;
            },
        ]);
    }

    private function getNodeQueryField(): array
    {
        return [
            'type' => $this->getNodeInterface(),
            'args' => [
                'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
            ],
            'resolve' => $this->itemResolver,
        ];
    }

    /**
     * Gets the query fields of the schema.
     */
    private function getQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata): array
    {
        $queryFields = [];
        $shortName = $resourceMetadata->getShortName();
        $deprecationReason = $resourceMetadata->getGraphqlAttribute('query', 'deprecation_reason', '', true);

        if ($fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, null, $deprecationReason, new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass), $resourceClass)) {
            $fieldConfiguration['args'] += ['id' => ['type' => GraphQLType::id()]];
            $queryFields[lcfirst($shortName)] = $fieldConfiguration;
        }

        if ($fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, null, $deprecationReason, new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, $resourceClass)), $resourceClass)) {
            $queryFields[lcfirst(Inflector::pluralize($shortName))] = $fieldConfiguration;
        }

        return $queryFields;
    }

    /**
     * Gets the mutation field for the given operation name.
     */
    private function getMutationFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $mutationName): array
    {
        $shortName = $resourceMetadata->getShortName();
        $resourceType = new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass);
        $deprecationReason = $resourceMetadata->getGraphqlAttribute($mutationName, 'deprecation_reason', '', true);

        if ($fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, ucfirst("{$mutationName}s a $shortName."), $deprecationReason, $resourceType, $resourceClass, false, $mutationName)) {
            $fieldConfiguration['args'] += ['input' => $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, null, $deprecationReason, $resourceType, $resourceClass, true, $mutationName)];

            if (!$this->isCollection($resourceType)) {
                $itemMutationResolverFactory = $this->itemMutationResolverFactory;
                $fieldConfiguration['resolve'] = $itemMutationResolverFactory($resourceClass, null, $mutationName);
            }
        }

        return $fieldConfiguration ?? [];
    }

    /**
     * Get the field configuration of a resource.
     *
     * @see http://webonyx.github.io/graphql-php/type-system/object-types/
     *
     * @return array|null
     */
    private function getResourceFieldConfiguration(string $resourceClass, ResourceMetadata $resourceMetadata, string $fieldDescription = null, string $deprecationReason = '', Type $type, string $rootResource, bool $input = false, string $mutationName = null, int $depth = 0)
    {
        try {
            if (null === $graphqlType = $this->convertType($type, $input, $mutationName, $depth)) {
                return null;
            }

            $graphqlWrappedType = $graphqlType instanceof WrappingType ? $graphqlType->getWrappedType() : $graphqlType;
            $isInternalGraphqlType = \in_array($graphqlWrappedType, GraphQLType::getInternalTypes(), true);
            if ($isInternalGraphqlType) {
                $className = '';
            } else {
                $className = $this->isCollection($type) ? $type->getCollectionValueType()->getClassName() : $type->getClassName();
            }

            $args = [];
            if (!$input && null === $mutationName && !$isInternalGraphqlType && $this->isCollection($type)) {
                if ($this->paginationEnabled) {
                    $args = [
                        'first' => [
                            'type' => GraphQLType::int(),
                            'description' => 'Returns the first n elements from the list.',
                        ],
                        'after' => [
                            'type' => GraphQLType::string(),
                            'description' => 'Returns the elements in the list that come after the specified cursor.',
                        ],
                    ];
                }

                foreach ($resourceMetadata->getGraphqlAttribute('query', 'filters', [], true) as $filterId) {
                    if (null === $this->filterLocator || !$this->filterLocator->has($filterId)) {
                        continue;
                    }

                    foreach ($this->filterLocator->get($filterId)->getDescription($resourceClass) as $key => $value) {
                        $nullable = isset($value['required']) ? !$value['required'] : true;
                        $filterType = \in_array($value['type'], Type::$builtinTypes, true) ? new Type($value['type'], $nullable) : new Type('object', $nullable, $value['type']);
                        $graphqlFilterType = $this->convertType($filterType, false, null, $depth);

                        if ('[]' === $newKey = substr($key, -2)) {
                            $key = $newKey;
                            $graphqlFilterType = GraphQLType::listOf($graphqlFilterType);
                        }

                        parse_str($key, $parsed);
                        array_walk_recursive($parsed, function (&$value) use ($graphqlFilterType) {
                            $value = $graphqlFilterType;
                        });
                        $args = $this->mergeFilterArgs($args, $parsed, $resourceMetadata, $key);
                    }
                }
                $args = $this->convertFilterArgsToTypes($args);
            }

            if ($isInternalGraphqlType || $input || null !== $mutationName) {
                $resolve = null;
            } elseif ($this->isCollection($type)) {
                $resolverFactory = $this->collectionResolverFactory;
                $resolve = $resolverFactory($className, $rootResource);
            } else {
                $resolve = $this->itemResolver;
            }

            return [
                'type' => $graphqlType,
                'description' => $fieldDescription,
                'args' => $args,
                'resolve' => $resolve,
                'deprecationReason' => $deprecationReason,
            ];
        } catch (InvalidTypeException $e) {
            // just ignore invalid types
        }

        return null;
    }

    private function mergeFilterArgs(array $args, array $parsed, ResourceMetadata $resourceMetadata = null, $original = ''): array
    {
        foreach ($parsed as $key => $value) {
            // Never override keys that cannot be merged
            if (isset($args[$key]) && !\is_array($args[$key])) {
                continue;
            }

            if (\is_array($value)) {
                $value = $this->mergeFilterArgs($args[$key] ?? [], $value);
                if (!isset($value['#name'])) {
                    $name = (false === $pos = strrpos($original, '[')) ? $original : substr($original, 0, $pos);
                    $value['#name'] = ($resourceMetadata ? $resourceMetadata->getShortName() : '').'Filter_'.strtr($name, ['[' => '_', ']' => '', '.' => '__']);
                }
            }

            $args[$key] = $value;
        }

        return $args;
    }

    private function convertFilterArgsToTypes(array $args): array
    {
        foreach ($args as $key => $value) {
            if (strpos($key, '.')) {
                // Declare relations/nested fields in a GraphQL compatible syntax.
                $args[str_replace('.', '_', $key)] = $value;
            }
        }

        foreach ($args as $key => $value) {
            if (!\is_array($value) || !isset($value['#name'])) {
                continue;
            }

            if (isset($this->graphqlTypes[$value['#name']])) {
                $args[$key] = $this->graphqlTypes[$value['#name']];
                continue;
            }

            $name = $value['#name'];
            unset($value['#name']);

            $this->graphqlTypes[$name] = $args[$key] = new InputObjectType([
                'name' => $name,
                'fields' => $this->convertFilterArgsToTypes($value),
            ]);
        }

        return $args;
    }

    /**
     * Converts a built-in type to its GraphQL equivalent.
     *
     * @throws InvalidTypeException
     */
    private function convertType(Type $type, bool $input = false, string $mutationName = null, int $depth = 0)
    {
        $resourceClass = null;
        switch ($builtinType = $type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_BOOL:
                $graphqlType = GraphQLType::boolean();
                break;
            case Type::BUILTIN_TYPE_INT:
                $graphqlType = GraphQLType::int();
                break;
            case Type::BUILTIN_TYPE_FLOAT:
                $graphqlType = GraphQLType::float();
                break;
            case Type::BUILTIN_TYPE_STRING:
                $graphqlType = GraphQLType::string();
                break;
            case Type::BUILTIN_TYPE_ARRAY:
            case Type::BUILTIN_TYPE_ITERABLE:
                if (!isset($this->graphqlTypes['#iterable'])) {
                    $this->graphqlTypes['#iterable'] = new IterableType();
                }
                $graphqlType = $this->graphqlTypes['#iterable'];
                break;
            case Type::BUILTIN_TYPE_OBJECT:
                if (($input && $depth > 0) || is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                    $graphqlType = GraphQLType::string();
                    break;
                }

                if (is_a($typeClass = $type->getClassName(), EnumInterface::class, true)) {
                    /** @var EnumInterface $typeClass */
                    $typeName = $typeClass::getName();
                    $graphqlTypeName = '#'.$typeName;
                    if (!isset($this->graphqlTypes[$graphqlTypeName])) {
                        $this->graphqlTypes[$graphqlTypeName] = new EnumType([
                            'name' => $typeName,
                            'values' => $typeClass::getValues(),
                        ]);
                    }

                    $graphqlType = $this->graphqlTypes[$graphqlTypeName];
                    break;
                }

                $resourceClass = $this->isCollection($type) ? $type->getCollectionValueType()->getClassName() : $type->getClassName();
                try {
                    $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
                    if ([] === $resourceMetadata->getGraphql() ?? []) {
                        return null;
                    }
                } catch (ResourceClassNotFoundException $e) {
                    // Skip objects that are not resources for now
                    return null;
                }

                $graphqlType = $this->getResourceObjectType($resourceClass, $resourceMetadata, $input, $mutationName, $depth);
                break;
            default:
                throw new InvalidTypeException(sprintf('The type "%s" is not supported.', $builtinType));
        }

        if ($this->isCollection($type)) {
            return $this->paginationEnabled && !$input ? $this->getResourcePaginatedCollectionType($resourceClass, $graphqlType) : GraphQLType::listOf($graphqlType);
        }

        return $type->isNullable() || (null !== $mutationName && 'update' === $mutationName) ? $graphqlType : GraphQLType::nonNull($graphqlType);
    }

    /**
     * Gets the object type of the given resource.
     *
     * @return ObjectType|InputObjectType
     */
    private function getResourceObjectType(string $resourceClass, ResourceMetadata $resourceMetadata, bool $input = false, string $mutationName = null, int $depth = 0): GraphQLType
    {
        if (isset($this->graphqlTypes[$resourceClass][$mutationName][$input])) {
            return $this->graphqlTypes[$resourceClass][$mutationName][$input];
        }

        $shortName = $resourceMetadata->getShortName();
        if (null !== $mutationName) {
            $shortName = $mutationName.ucfirst($shortName);
        }
        if ($input) {
            $shortName .= 'Input';
        } elseif (null !== $mutationName) {
            $shortName .= 'Payload';
        }

        $configuration = [
            'name' => $shortName,
            'description' => $resourceMetadata->getDescription(),
            'resolveField' => $this->defaultFieldResolver,
            'fields' => function () use ($resourceClass, $resourceMetadata, $input, $mutationName, $depth) {
                return $this->getResourceObjectTypeFields($resourceClass, $resourceMetadata, $input, $mutationName, $depth);
            },
            'interfaces' => [$this->getNodeInterface()],
        ];

        return $this->graphqlTypes[$resourceClass][$mutationName][$input] = $input ? new InputObjectType($configuration) : new ObjectType($configuration);
    }

    /**
     * Gets the fields of the type of the given resource.
     */
    private function getResourceObjectTypeFields(string $resourceClass, ResourceMetadata $resourceMetadata, bool $input = false, string $mutationName = null, int $depth = 0): array
    {
        $fields = [];
        $idField = ['type' => GraphQLType::nonNull(GraphQLType::id())];
        $clientMutationId = GraphQLType::nonNull(GraphQLType::string());

        if ('delete' === $mutationName) {
            return [
                'id' => $idField,
                'clientMutationId' => $clientMutationId,
            ];
        }

        if (!$input || 'create' !== $mutationName) {
            $fields['id'] = $idField;
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property, ['graphql_operation_name' => $mutationName ?? 'query']);
            if (
                null === ($propertyType = $propertyMetadata->getType())
                || (!$input && null === $mutationName && false === $propertyMetadata->isReadable())
                || (null !== $mutationName && false === $propertyMetadata->isWritable())
            ) {
                continue;
            }

            if ($fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, $propertyMetadata->getDescription(), $propertyMetadata->getAttribute('deprecation_reason', ''), $propertyType, $resourceClass, $input, $mutationName, ++$depth)) {
                $fields['id' === $property ? '_id' : $property] = $fieldConfiguration;
            }
        }

        if (null !== $mutationName) {
            $fields['clientMutationId'] = $clientMutationId;
        }

        return $fields;
    }

    /**
     * Gets the type of a paginated collection of the given resource type.
     *
     * @param ObjectType $resourceType
     *
     * @return ObjectType
     */
    private function getResourcePaginatedCollectionType(string $resourceClass, GraphQLType $resourceType): GraphQLType
    {
        $shortName = $resourceType->name;

        if (isset($this->graphqlTypes[$resourceClass]['connection'])) {
            return $this->graphqlTypes[$resourceClass]['connection'];
        }

        $edgeObjectTypeConfiguration = [
            'name' => "{$shortName}Edge",
            'description' => "Edge of $shortName.",
            'fields' => [
                'node' => $resourceType,
                'cursor' => GraphQLType::nonNull(GraphQLType::string()),
            ],
        ];
        $edgeObjectType = new ObjectType($edgeObjectTypeConfiguration);
        $pageInfoObjectTypeConfiguration = [
            'name' => "{$shortName}PageInfo",
            'description' => 'Information about the current page.',
            'fields' => [
                'endCursor' => GraphQLType::string(),
                'hasNextPage' => GraphQLType::nonNull(GraphQLType::boolean()),
            ],
        ];
        $pageInfoObjectType = new ObjectType($pageInfoObjectTypeConfiguration);

        $configuration = [
            'name' => "{$shortName}Connection",
            'description' => "Connection for $shortName.",
            'fields' => [
                'edges' => GraphQLType::listOf($edgeObjectType),
                'pageInfo' => GraphQLType::nonNull($pageInfoObjectType),
                'totalCount' => GraphQLType::nonNull(GraphQLType::int()),
            ],
        ];

        return $this->graphqlTypes[$resourceClass]['connection'] = new ObjectType($configuration);
    }

    private function isCollection(Type $type): bool
    {
        return $type->isCollection() && Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType();
    }
}
