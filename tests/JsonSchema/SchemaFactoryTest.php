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

namespace ApiPlatform\Core\Tests\JsonSchema;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\JsonSchema\Schema;
use ApiPlatform\Core\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\OverriddenOperationDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class SchemaFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testBuildSchemaForNonResourceClass(): void
    {
        $typeFactoryProphecy = $this->prophesize(TypeFactoryInterface::class);
        $typeFactoryProphecy->getType(Argument::allOf(
            Argument::type(Type::class),
            Argument::which('getBuiltinType', Type::BUILTIN_TYPE_STRING)
        ), Argument::cetera())->willReturn([
            'type' => 'string',
        ]);
        $typeFactoryProphecy->getType(Argument::allOf(
            Argument::type(Type::class),
            Argument::which('getBuiltinType', Type::BUILTIN_TYPE_INT)
        ), Argument::cetera())->willReturn([
            'type' => 'integer',
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(NotAResource::class, Argument::cetera())->willReturn(new PropertyNameCollection(['foo', 'bar']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'foo', Argument::cetera())->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), null, true, null, null, null, null, null, null, null, null, null, null, '', ''));
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'bar', Argument::cetera())->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), null, true, null, null, null, null, null, null, null, null, null, null, 'default_bar', 'example_bar'));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(NotAResource::class)->willReturn(false);

        $schemaFactory = new SchemaFactory($typeFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());
        $resultSchema = $schemaFactory->buildSchema(NotAResource::class);

        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $definitions = $resultSchema->getDefinitions();

        $this->assertSame((new \ReflectionClass(NotAResource::class))->getShortName(), $rootDefinitionKey);
        $this->assertArrayHasKey($rootDefinitionKey, $definitions->getArrayCopy());
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['type']);
        $this->assertArrayNotHasKey('additionalProperties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('foo', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertArrayNotHasKey('default', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertArrayNotHasKey('example', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['foo']['type']);
        $this->assertArrayHasKey('bar', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertArrayHasKey('default', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertArrayHasKey('example', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertSame('integer', $definitions[$rootDefinitionKey]['properties']['bar']['type']);
        $this->assertSame('default_bar', $definitions[$rootDefinitionKey]['properties']['bar']['default']);
        $this->assertSame('example_bar', $definitions[$rootDefinitionKey]['properties']['bar']['example']);
    }

    public function testBuildSchemaForOperationWithOverriddenSerializerGroups(): void
    {
        $typeFactoryProphecy = $this->prophesize(TypeFactoryInterface::class);
        $typeFactoryProphecy->getType(Argument::allOf(
            Argument::type(Type::class),
            Argument::which('getBuiltinType', Type::BUILTIN_TYPE_STRING)
        ), Argument::cetera())->willReturn([
            'type' => 'string',
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(OverriddenOperationDummy::class)->willReturn(new ResourceMetadata((new \ReflectionClass(OverriddenOperationDummy::class))->getShortName(), null, null, [
            'put' => [
                'normalization_context' => [
                    'groups' => 'overridden_operation_dummy_put',
                    AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                ],
                'validation_groups' => ['validation_groups_dummy_put'],
            ],
        ], [], [
            'normalization_context' => [
                'groups' => 'overridden_operation_dummy_read',
            ],
        ]));

        $serializerGroup = 'overridden_operation_dummy_put';
        $validationGroups = 'validation_groups_dummy_put';

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(OverriddenOperationDummy::class, Argument::allOf(
            Argument::type('array'),
            Argument::allOf(Argument::withEntry('serializer_groups', [$serializerGroup]), Argument::withEntry('validation_groups', [$validationGroups]))
        ))->willReturn(new PropertyNameCollection(['alias', 'description']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(OverriddenOperationDummy::class, 'alias', Argument::allOf(
            Argument::type('array'),
            Argument::allOf(Argument::withEntry('serializer_groups', [$serializerGroup]), Argument::withEntry('validation_groups', [$validationGroups]))
        ))->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), null, true));
        $propertyMetadataFactoryProphecy->create(OverriddenOperationDummy::class, 'description', Argument::allOf(
            Argument::type('array'),
            Argument::allOf(Argument::withEntry('serializer_groups', [$serializerGroup]), Argument::withEntry('validation_groups', [$validationGroups]))
        ))->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), null, true));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(OverriddenOperationDummy::class)->willReturn(true);

        $schemaFactory = new SchemaFactory($typeFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());
        $resultSchema = $schemaFactory->buildSchema(OverriddenOperationDummy::class, 'json', Schema::TYPE_OUTPUT, OperationType::ITEM, 'put');

        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $definitions = $resultSchema->getDefinitions();

        $this->assertSame((new \ReflectionClass(OverriddenOperationDummy::class))->getShortName().'-'.$serializerGroup, $rootDefinitionKey);
        $this->assertArrayHasKey($rootDefinitionKey, $definitions->getArrayCopy());
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['type']);
        $this->assertFalse($definitions[$rootDefinitionKey]['additionalProperties']);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('alias', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['alias']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['alias']['type']);
        $this->assertArrayHasKey('description', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['description']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['description']['type']);
    }
}
