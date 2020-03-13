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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

class SchemaFactoryTest extends TestCase
{
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
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'foo', Argument::cetera())->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), null, true));
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'bar', Argument::cetera())->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), null, true));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(NotAResource::class)->willReturn(false);

        $serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $serializerContextFactoryProphecy->create(NotAResource::class, 'resource', Argument::type('bool'), [
            'resource_operation_name' => 'resource',
        ])->willReturn([]);

        $schemaFactory = new SchemaFactory($typeFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal(), $serializerContextFactoryProphecy->reveal());
        $resultSchema = $schemaFactory->buildSchema(NotAResource::class);

        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $definitions = $resultSchema->getDefinitions();

        $this->assertSame((new \ReflectionClass(NotAResource::class))->getShortName(), $rootDefinitionKey);
        $this->assertArrayHasKey($rootDefinitionKey, $definitions);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['type']);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('foo', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['foo']['type']);
        $this->assertArrayHasKey('bar', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertSame('integer', $definitions[$rootDefinitionKey]['properties']['bar']['type']);
    }
}
