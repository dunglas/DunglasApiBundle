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

namespace ApiPlatform\Core\Tests\GraphQl\Type;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Core\GraphQl\Type\FieldsBuilder;
use ApiPlatform\Core\GraphQl\Type\TypeBuilderInterface;
use ApiPlatform\Core\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\Core\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class FieldsBuilderTest extends TestCase
{
    /** @var ObjectProphecy */
    private $propertyNameCollectionFactoryProphecy;

    /** @var ObjectProphecy */
    private $propertyMetadataFactoryProphecy;

    /** @var ObjectProphecy */
    private $resourceMetadataFactoryProphecy;

    /** @var ObjectProphecy */
    private $typesContainerProphecy;

    /** @var ObjectProphecy */
    private $typeBuilderProphecy;

    /** @var ObjectProphecy */
    private $typeConverterProphecy;

    /** @var ObjectProphecy */
    private $itemResolverFactoryProphecy;

    /** @var ObjectProphecy */
    private $collectionResolverFactoryProphecy;

    /** @var ObjectProphecy */
    private $itemMutationResolverFactoryProphecy;

    /** @var ObjectProphecy */
    private $filterLocatorProphecy;

    /** @var FieldsBuilder */
    private $fieldsBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $this->propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->typeBuilderProphecy = $this->prophesize(TypeBuilderInterface::class);
        $this->typeConverterProphecy = $this->prophesize(TypeConverterInterface::class);
        $this->itemResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $this->collectionResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $this->itemMutationResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $this->filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->fieldsBuilder = new FieldsBuilder($this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->typesContainerProphecy->reveal(), $this->typeBuilderProphecy->reveal(), $this->typeConverterProphecy->reveal(), $this->itemResolverFactoryProphecy->reveal(), $this->collectionResolverFactoryProphecy->reveal(), $this->itemMutationResolverFactoryProphecy->reveal(), $this->filterLocatorProphecy->reveal(), true);
    }

    public function testGetNodeQueryFields(): void
    {
        $nodeInterfaceType = $this->prophesize(InterfaceType::class)->reveal();
        $this->typeBuilderProphecy->getNodeInterface()->shouldBeCalled()->willReturn($nodeInterfaceType);

        $itemResolver = function () {
        };
        $this->itemResolverFactoryProphecy->__invoke()->shouldBeCalled()->willReturn($itemResolver);

        $nodeQueryFields = $this->fieldsBuilder->getNodeQueryFields();
        $this->assertArrayHasKey('type', $nodeQueryFields);
        $this->assertArrayHasKey('args', $nodeQueryFields);
        $this->assertArrayHasKey('resolve', $nodeQueryFields);

        $this->assertSame($nodeInterfaceType, $nodeQueryFields['type']);
        $this->assertArrayHasKey('id', $nodeQueryFields['args']);
        $this->assertArrayHasKey('type', $nodeQueryFields['args']['id']);
        $this->assertInstanceOf(NonNull::class, $nodeQueryFields['args']['id']['type']);
        /** @var NonNull $idType */
        $idType = $nodeQueryFields['args']['id']['type'];
        $this->assertSame(GraphQLType::id(), $idType->getWrappedType());
        $this->assertSame($itemResolver, $nodeQueryFields['resolve']);
    }

    /**
     * @dataProvider queryFieldsProvider
     */
    public function testGetQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $queryName, $itemConfiguration, $collectionConfiguration, GraphQLType $graphqlType, bool $isTypeCollection, ?callable $itemResolve, ?callable $collectionResolve, array $expectedQueryFields): void
    {
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, $queryName, null, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn($isTypeCollection);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType)->willReturn($graphqlType);
        $this->itemResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $queryName)->willReturn($itemResolve);
        $this->collectionResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $queryName)->willReturn($collectionResolve);
        $this->filterLocatorProphecy->has('my_filter')->willReturn(true);
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->getDescription($resourceClass)->willReturn([
            'boolField' => ['type' => 'bool', 'required' => true],
            'boolField[]' => ['type' => 'bool', 'required' => true],
            'parent.child[related.nested]' => ['type' => 'bool', 'required' => true],
            'dateField[before]' => ['type' => \DateTimeInterface::class, 'required' => false],
        ]);
        $this->filterLocatorProphecy->get('my_filter')->willReturn($filterProphecy->reveal());
        $this->typesContainerProphecy->has('ShortNameFilter_dateField')->willReturn(false);
        $this->typesContainerProphecy->has('ShortNameFilter_parent__child')->willReturn(false);
        $this->typesContainerProphecy->set('ShortNameFilter_dateField', Argument::type(InputObjectType::class));
        $this->typesContainerProphecy->set('ShortNameFilter_parent__child', Argument::type(InputObjectType::class));

        $queryFields = $this->fieldsBuilder->getQueryFields($resourceClass, $resourceMetadata, $queryName, $itemConfiguration, $collectionConfiguration);

        $this->assertEquals($expectedQueryFields, $queryFields);
    }

    public function queryFieldsProvider(): array
    {
        return [
            'no configuration' => ['resourceClass', new ResourceMetadata(), 'action', false, false, GraphQLType::string(), false, null, null, []],
            'nominal standard type case with deprecation reason' => ['resourceClass', (new ResourceMetadata('ShortName'))->withGraphql(['action' => ['deprecation_reason' => 'not useful']]), 'action', [], false, GraphQLType::string(), false, null, null,
                [
                    'actionShortName' => [
                        'type' => GraphQLType::string(),
                        'description' => null,
                        'args' => [
                            'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
                        ],
                        'resolve' => null,
                        'deprecationReason' => 'not useful',
                    ],
                ],
            ],
            'nominal item case' => ['resourceClass', new ResourceMetadata('ShortName'), 'action', [], false, $graphqlType = new ObjectType(['name' => 'item']), false, $itemResolve = function () {
            }, null,
                [
                    'actionShortName' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
                            'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
                        ],
                        'resolve' => $itemResolve,
                        'deprecationReason' => '',
                    ],
                ],
            ],
            'empty overridden args and add fields' => [
                'resourceClass', new ResourceMetadata('ShortName'), 'query', ['args' => [], 'name' => 'customActionName'], false, GraphQLType::string(), false, null, null,
                [
                    'shortName' => [
                        'type' => GraphQLType::string(),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                        'name' => 'customActionName',
                    ],
                ],
            ],
            'override args with custom ones' => [
                'resourceClass', new ResourceMetadata('ShortName'), 'query', ['args' => ['customArg' => ['type' => 'a type']]], false, GraphQLType::string(), false, null, null,
                [
                    'shortName' => [
                        'type' => GraphQLType::string(),
                        'description' => null,
                        'args' => [
                            'customArg' => [
                                'type' => GraphQLType::string(),
                            ],
                        ],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                ],
            ],
            'wrapped collection type' => ['resourceClass', new ResourceMetadata('ShortName'), 'action', false, [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), true, null, $collectionResolve = function () {
            },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
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
                        ],
                        'resolve' => $collectionResolve,
                        'deprecationReason' => '',
                    ],
                ],
            ],
            'collection with filters' => ['resourceClass', (new ResourceMetadata('ShortName'))->withGraphql(['action' => ['filters' => ['my_filter']]]), 'action', false, [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), true, null, $collectionResolve = function () {
            },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
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
                            'boolField' => $graphqlType,
                            'boolField_list' => GraphQLType::listOf($graphqlType),
                            'parent_child' => new InputObjectType(['name' => 'ShortNameFilter_parent__child', 'fields' => ['related_nested' => $graphqlType]]),
                            'dateField' => new InputObjectType(['name' => 'ShortNameFilter_dateField', 'fields' => ['before' => $graphqlType]]),
                        ],
                        'resolve' => $collectionResolve,
                        'deprecationReason' => '',
                    ],
                ],
            ],
            'collection empty overridden args and add fields' => [
                'resourceClass', new ResourceMetadata('ShortName'), 'action', false, ['args' => [], 'name' => 'customActionName'], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), true, null, $collectionResolve = function () {
                },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [],
                        'resolve' => $collectionResolve,
                        'deprecationReason' => '',
                        'name' => 'customActionName',
                    ],
                ],
            ],
            'collection override args with custom ones' => [
                'resourceClass', new ResourceMetadata('ShortName'), 'action', false, ['args' => ['customArg' => ['type' => 'a type']]], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), true, null, $collectionResolve = function () {
                },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
                            'customArg' => [
                                'type' => GraphQLType::string(),
                            ],
                        ],
                        'resolve' => $collectionResolve,
                        'deprecationReason' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider mutationFieldsProvider
     */
    public function testGetMutationFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $mutationName, GraphQLType $graphqlType, bool $isTypeCollection, ?callable $mutationResolve, ?callable $collectionResolve, array $expectedMutationFields): void
    {
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, null, $mutationName, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, null, $mutationName, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn($isTypeCollection);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType)->willReturn($graphqlType);
        $this->collectionResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, null)->willReturn($collectionResolve);
        $this->itemMutationResolverFactoryProphecy->__invoke($resourceClass, null, $mutationName)->willReturn($mutationResolve);

        $mutationFields = $this->fieldsBuilder->getMutationFields($resourceClass, $resourceMetadata, $mutationName);

        $this->assertEquals($expectedMutationFields, $mutationFields);
    }

    public function mutationFieldsProvider(): array
    {
        return [
            'nominal case with deprecation reason' => ['resourceClass', (new ResourceMetadata('ShortName'))->withGraphql(['action' => ['deprecation_reason' => 'not useful']]), 'action', GraphQLType::string(), false, $mutationResolve = function () {
            }, null,
                [
                    'actionShortName' => [
                        'type' => GraphQLType::string(),
                        'description' => 'Actions a ShortName.',
                        'args' => [
                            'input' => [
                                'type' => GraphQLType::string(),
                                'description' => null,
                                'args' => [],
                                'resolve' => null,
                                'deprecationReason' => 'not useful',
                            ],
                        ],
                        'resolve' => $mutationResolve,
                        'deprecationReason' => 'not useful',
                    ],
                ],
            ],
            'wrapped collection type' => ['resourceClass', new ResourceMetadata('ShortName'), 'action', $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), true, null, $collectionResolve = function () {
            },
                [
                    'actionShortName' => [
                        'type' => $graphqlType,
                        'description' => 'Actions a ShortName.',
                        'args' => [
                            'input' => [
                                'type' => GraphQLType::listOf($graphqlType),
                                'description' => null,
                                'args' => [],
                                'resolve' => null,
                                'deprecationReason' => '',
                            ],
                        ],
                        'resolve' => $collectionResolve,
                        'deprecationReason' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider resourceObjectTypeFieldsProvider
     */
    public function testGetResourceObjectTypeFields(string $resourceClass, ResourceMetadata $resourceMetadata, array $properties, bool $input, ?string $queryName, ?string $mutationName, ?array $ioMetadata, array $expectedResourceObjectTypeFields): void
    {
        $this->propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection(array_keys($properties)));
        foreach ($properties as $propertyName => $propertyMetadata) {
            $this->propertyMetadataFactoryProphecy->create($resourceClass, $propertyName, ['graphql_operation_name' => $queryName ?? $mutationName])->willReturn($propertyMetadata);
            $this->propertyMetadataFactoryProphecy->create($resourceClass, $propertyName, ['graphql_operation_name' => $queryName ?? $mutationName])->willReturn($propertyMetadata);
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_NULL), Argument::type('bool'), $queryName, null, $resourceClass, $propertyName, 1)->willReturn(null);
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_CALLABLE), Argument::type('bool'), $queryName, null, $resourceClass, $propertyName, 1)->willReturn('NotRegisteredType');
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), $queryName, null, $resourceClass, $propertyName, 1)->willReturn(GraphQLType::string());
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), null, $mutationName, $resourceClass, $propertyName, 1)->willReturn(GraphQLType::string());
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, null, $mutationName, 'subresourceClass', $propertyName, 1)->willReturn(GraphQLType::string());
        }
        $this->typesContainerProphecy->has('NotRegisteredType')->willReturn(false);
        $this->typesContainerProphecy->all()->willReturn([]);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->resourceMetadataFactoryProphecy->create('subresourceClass')->willReturn(new ResourceMetadata());

        $resourceObjectTypeFields = $this->fieldsBuilder->getResourceObjectTypeFields($resourceClass, $resourceMetadata, $input, $queryName, $mutationName, 0, $ioMetadata);

        $this->assertEquals($expectedResourceObjectTypeFields, $resourceObjectTypeFields);
    }

    public function resourceObjectTypeFieldsProvider(): array
    {
        return [
            'query' => ['resourceClass', new ResourceMetadata(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, true, false),
                    'propertyNotReadable' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, false),
                ],
                false, 'query', null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                ],
            ],
            'query input' => ['resourceClass', new ResourceMetadata(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, false),
                ],
                true, 'query', null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                ],
            ],
            'mutation non input' => ['resourceClass', new ResourceMetadata(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                    'propertyReadable' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, true, true),
                ],
                false, null, 'mutation', null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyReadable' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                ],
            ],
            'mutation input' => ['resourceClass', new ResourceMetadata(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), 'propertyBool description', false, true))->withAttributes(['deprecation_reason' => 'not useful']),
                    'propertySubresource' => (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true))->withSubresource(new SubresourceMetadata('subresourceClass')),
                    'id' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), null, false, true),
                ],
                true, null, 'mutation', null,
                [
                    'id' => [
                        'type' => GraphQLType::id(),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => 'propertyBool description',
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => 'not useful',
                    ],
                    'propertySubresource' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                    '_id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'delete mutation input' => ['resourceClass', new ResourceMetadata(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'delete', null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'create mutation input' => ['resourceClass', new ResourceMetadata(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'create', null,
                [
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'update mutation input' => ['resourceClass', new ResourceMetadata(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'update', null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::string(),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => '',
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'null io metadata non input' => ['resourceClass', new ResourceMetadata(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                false, null, 'update', ['class' => null], [],
            ],
            'null io metadata input' => ['resourceClass', new ResourceMetadata(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'update', ['class' => null],
                [
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'invalid types' => ['resourceClass', new ResourceMetadata(),
                [
                    'propertyInvalidType' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_NULL), null, true, false),
                    'propertyNotRegisteredType' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_CALLABLE), null, true, false),
                ],
                false, 'query', null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider resolveResourceArgsProvider
     */
    public function testResolveResourceArgs(array $args, array $expectedResolvedArgs, ?string $expectedExceptionMessage = null): void
    {
        if (null !== $expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());

        $args = $this->fieldsBuilder->resolveResourceArgs($args, 'operation', 'shortName');

        $this->assertSame($expectedResolvedArgs, $args);
    }

    public function resolveResourceArgsProvider(): array
    {
        return [
            [[], []],
            [['customArg' => []], [], 'The argument "customArg" of the custom operation "operation" in shortName needs a "type" option.'],
            [['customArg' => ['type' => 'a type']], ['customArg' => ['type' => GraphQLType::string()]]],
        ];
    }
}
