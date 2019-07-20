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

use ApiPlatform\Core\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Inflector\Inflector;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\WrappingType;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\PropertyInfo\Type;

/**
 * Builds the GraphQL fields.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class FieldsBuilder implements FieldsBuilderInterface
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceMetadataFactory;
    private $typesContainer;
    private $typeBuilder;
    private $typeConverter;
    private $itemResolverFactory;
    private $collectionResolverFactory;
    private $itemMutationResolverFactory;
    private $filterLocator;
    private $paginationEnabled;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, TypesContainerInterface $typesContainer, TypeBuilderInterface $typeBuilder, TypeConverterInterface $typeConverter, ResolverFactoryInterface $itemResolverFactory, ResolverFactoryInterface $collectionResolverFactory, ResolverFactoryInterface $itemMutationResolverFactory, ContainerInterface $filterLocator, bool $paginationEnabled)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->typesContainer = $typesContainer;
        $this->typeBuilder = $typeBuilder;
        $this->typeConverter = $typeConverter;
        $this->itemResolverFactory = $itemResolverFactory;
        $this->collectionResolverFactory = $collectionResolverFactory;
        $this->itemMutationResolverFactory = $itemMutationResolverFactory;
        $this->filterLocator = $filterLocator;
        $this->paginationEnabled = $paginationEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeQueryFields(): array
    {
        return [
            'type' => $this->typeBuilder->getNodeInterface(),
            'args' => [
                'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
            ],
            'resolve' => ($this->itemResolverFactory)(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $queryName, $itemConfiguration, $collectionConfiguration): array
    {
        $queryFields = [];
        $shortName = $resourceMetadata->getShortName();
        $fieldName = lcfirst('query' === $queryName ? $shortName : $queryName.$shortName);

        $deprecationReason = $resourceMetadata->getGraphqlAttribute($queryName, 'deprecation_reason', '', true);

        if (false !== $itemConfiguration && $fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, null, null, $deprecationReason, new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass), $resourceClass, false, $queryName, null)) {
            $args = $this->resolveResourceArgs($itemConfiguration['args'] ?? [], $queryName, $shortName);
            $itemConfiguration['args'] = $args ?: $itemConfiguration['args'] ?? ['id' => ['type' => GraphQLType::nonNull(GraphQLType::id())]];

            $queryFields[$fieldName] = array_merge($fieldConfiguration, $itemConfiguration);
        }

        if (false !== $collectionConfiguration && $fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, null, null, $deprecationReason, new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, $resourceClass)), $resourceClass, false, $queryName, null)) {
            $args = $this->resolveResourceArgs($collectionConfiguration['args'] ?? [], $queryName, $shortName);
            $collectionConfiguration['args'] = $args ?: $collectionConfiguration['args'] ?? $fieldConfiguration['args'];

            $queryFields[Inflector::pluralize($fieldName)] = array_merge($fieldConfiguration, $collectionConfiguration);
        }

        return $queryFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getMutationFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $mutationName): array
    {
        $mutationFields = [];
        $shortName = $resourceMetadata->getShortName();
        $resourceType = new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass);
        $deprecationReason = $resourceMetadata->getGraphqlAttribute($mutationName, 'deprecation_reason', '', true);

        if ($fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, null, ucfirst("{$mutationName}s a $shortName."), $deprecationReason, $resourceType, $resourceClass, false, null, $mutationName)) {
            $fieldConfiguration['args'] += ['input' => $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, null, null, $deprecationReason, $resourceType, $resourceClass, true, null, $mutationName)];

            if (!$this->typeBuilder->isCollection($resourceType)) {
                $fieldConfiguration['resolve'] = ($this->itemMutationResolverFactory)($resourceClass, null, $mutationName);
            }
        }

        $mutationFields[$mutationName.$resourceMetadata->getShortName()] = $fieldConfiguration ?? [];

        return $mutationFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceObjectTypeFields(?string $resourceClass, ResourceMetadata $resourceMetadata, bool $input, ?string $queryName, ?string $mutationName, int $depth = 0, ?array $ioMetadata = null): array
    {
        $fields = [];
        $optionalIdField = ['type' => GraphQLType::id()];
        $requiredIdField = ['type' => GraphQLType::nonNull(GraphQLType::id())];
        $clientMutationId = GraphQLType::string();

        if (null !== $ioMetadata && \array_key_exists('class', $ioMetadata) && null === $ioMetadata['class']) {
            if ($input) {
                return ['clientMutationId' => $clientMutationId];
            }

            return [];
        }

        if ('delete' === $mutationName) {
            $fields = [
                'id' => $requiredIdField,
            ];

            if ($input) {
                $fields['clientMutationId'] = $clientMutationId;
            }

            return $fields;
        }

        if (!$input || !$mutationName || \in_array($mutationName, ['update', 'delete'], true)) {
            $fields['id'] = $requiredIdField;
        } elseif ('create' !== $mutationName) {
            $fields['id'] = $optionalIdField;
        }

        ++$depth; // increment the depth for the call to getResourceFieldConfiguration.

        if (null !== $resourceClass) {
            foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property, ['graphql_operation_name' => $mutationName ?? $queryName ?? 'query']);
                if (
                    null === ($propertyType = $propertyMetadata->getType())
                    || (!$input && false === $propertyMetadata->isReadable())
                    || ($input && null !== $mutationName && false === $propertyMetadata->isWritable())
                ) {
                    continue;
                }

                $rootResource = $resourceClass;
                if (null !== $propertyMetadata->getSubresource()) {
                    $resourceClass = $propertyMetadata->getSubresource()->getResourceClass();
                    $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
                }
                if ($fieldConfiguration = $this->getResourceFieldConfiguration($resourceClass, $resourceMetadata, $property, $propertyMetadata->getDescription(), $propertyMetadata->getAttribute('deprecation_reason', ''), $propertyType, $rootResource, $input, $queryName, $mutationName, $depth)) {
                    $fields['id' === $property ? '_id' : $property] = $fieldConfiguration;
                }
                $resourceClass = $rootResource;
            }
        }

        if (null !== $mutationName && $input) {
            $fields['clientMutationId'] = $clientMutationId;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveResourceArgs(array $args, string $operationName, string $shortName): array
    {
        foreach ($args as $id => $arg) {
            if (!isset($arg['type'])) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" of the custom operation "%s" in %s needs a "type" option.', $id, $operationName, $shortName));
            }

            $args[$id]['type'] = $this->typeConverter->resolveType($arg['type']);
        }

        return $args;
    }

    /**
     * Get the field configuration of a resource.
     *
     * @see http://webonyx.github.io/graphql-php/type-system/object-types/
     */
    private function getResourceFieldConfiguration(string $resourceClass, ResourceMetadata $resourceMetadata, ?string $property, ?string $fieldDescription, string $deprecationReason, Type $type, string $rootResource, bool $input, ?string $queryName, ?string $mutationName, int $depth = 0): ?array
    {
        try {
            if (null === $graphqlType = $this->convertType($type, $input, $queryName, $mutationName, $resourceClass, $property, $depth)) {
                return null;
            }

            $graphqlWrappedType = $graphqlType instanceof WrappingType ? $graphqlType->getWrappedType() : $graphqlType;
            $isStandardGraphqlType = \in_array($graphqlWrappedType, GraphQLType::getStandardTypes(), true);
            if ($isStandardGraphqlType) {
                $className = '';
            } else {
                $className = $this->typeBuilder->isCollection($type) && ($collectionValueType = $type->getCollectionValueType()) ? $collectionValueType->getClassName() : $type->getClassName();
            }

            $args = [];
            if (!$input && null === $mutationName && !$isStandardGraphqlType && $this->typeBuilder->isCollection($type)) {
                if ($this->paginationEnabled) {
                    $args = [
                        'first' => [
                            'type' => GraphQLType::int(),
                            'description' => 'Returns the first n elements from the list.',
                        ],
                        'last' => [
                            'type' => GraphQLType::int(),
                            'description' => 'Returns the last n elements from the list.',
                        ],
                        'before' => [
                            'type' => GraphQLType::string(),
                            'description' => 'Returns the elements in the list that come before the specified cursor.',
                        ],
                        'after' => [
                            'type' => GraphQLType::string(),
                            'description' => 'Returns the elements in the list that come after the specified cursor.',
                        ],
                    ];
                }

                foreach ($resourceMetadata->getGraphqlAttribute($queryName ?? 'query', 'filters', [], true) as $filterId) {
                    if (null === $this->filterLocator || !$this->filterLocator->has($filterId)) {
                        continue;
                    }

                    foreach ($this->filterLocator->get($filterId)->getDescription($resourceClass) as $key => $value) {
                        $nullable = isset($value['required']) ? !$value['required'] : true;
                        $filterType = \in_array($value['type'], Type::$builtinTypes, true) ? new Type($value['type'], $nullable) : new Type('object', $nullable, $value['type']);
                        $graphqlFilterType = $this->convertType($filterType, false, $queryName, $mutationName, $resourceClass, $property, $depth);

                        if ('[]' === substr($key, -2)) {
                            $graphqlFilterType = GraphQLType::listOf($graphqlFilterType);
                            $key = substr($key, 0, -2).'_list';
                        }

                        parse_str($key, $parsed);
                        if (\array_key_exists($key, $parsed) && \is_array($parsed[$key])) {
                            $parsed = [$key => ''];
                        }
                        array_walk_recursive($parsed, function (&$value) use ($graphqlFilterType) {
                            $value = $graphqlFilterType;
                        });
                        $args = $this->mergeFilterArgs($args, $parsed, $resourceMetadata, $key);
                    }
                }
                $args = $this->convertFilterArgsToTypes($args);
            }

            if ($isStandardGraphqlType || $input) {
                $resolve = null;
            } elseif ($this->typeBuilder->isCollection($type)) {
                $resolve = ($this->collectionResolverFactory)($className, $rootResource, $queryName);
            } else {
                $resolve = ($this->itemResolverFactory)($className, $rootResource, $queryName);
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
                    $name = (false === $pos = strrpos($original, '[')) ? $original : substr($original, 0, (int) $pos);
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
                unset($args[$key]);
            }
        }

        foreach ($args as $key => $value) {
            if (!\is_array($value) || !isset($value['#name'])) {
                continue;
            }

            $name = $value['#name'];

            if ($this->typesContainer->has($name)) {
                $args[$key] = $this->typesContainer->get($name);
                continue;
            }

            unset($value['#name']);

            $filterArgType = new InputObjectType([
                'name' => $name,
                'fields' => $this->convertFilterArgsToTypes($value),
            ]);

            $this->typesContainer->set($name, $filterArgType);

            $args[$key] = $filterArgType;
        }

        return $args;
    }

    /**
     * Converts a built-in type to its GraphQL equivalent.
     *
     * @throws InvalidTypeException
     */
    private function convertType(Type $type, bool $input, ?string $queryName, ?string $mutationName, string $resourceClass, ?string $property, int $depth)
    {
        $graphqlType = $this->typeConverter->convertType($type, $input, $queryName, $mutationName, $resourceClass, $property, $depth);

        if (null === $graphqlType) {
            throw new InvalidTypeException(sprintf('The type "%s" is not supported.', $type->getBuiltinType()));
        }

        if (\is_string($graphqlType)) {
            if (!$this->typesContainer->has($graphqlType)) {
                throw new InvalidTypeException(sprintf('The GraphQL type %s is not valid. Valid types are: %s. Have you registered this type by implementing %s?', $graphqlType, implode(', ', array_keys($this->typesContainer->all())), TypeInterface::class));
            }

            $graphqlType = $this->typesContainer->get($graphqlType);
        }

        if ($this->typeBuilder->isCollection($type)) {
            return $this->paginationEnabled && !$input ? $this->typeBuilder->getResourcePaginatedCollectionType($graphqlType) : GraphQLType::listOf($graphqlType);
        }

        return $type->isNullable() || (null !== $mutationName && 'update' === $mutationName) ? $graphqlType : GraphQLType::nonNull($graphqlType);
    }
}
