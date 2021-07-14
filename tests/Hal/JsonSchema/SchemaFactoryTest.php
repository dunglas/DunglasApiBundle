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

namespace ApiPlatform\Core\Tests\Hal\JsonSchema;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Hal\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\Schema;
use ApiPlatform\Core\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Core\Hydra\JsonSchema\SchemaFactory as HydraSchemaFactory;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

class SchemaFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $schemaFactory;

    protected function setUp(): void
    {
        $typeFactory = $this->prophesize(TypeFactoryInterface::class);
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(new ResourceMetadata(Dummy::class));
        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, ['enable_getter_setter_extraction' => true])->willReturn(new PropertyNameCollection());
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $baseSchemaFactory = new BaseSchemaFactory(
            $typeFactory->reveal(),
            $resourceMetadataFactory->reveal(),
            $propertyNameCollectionFactory->reveal(),
            $propertyMetadataFactory->reveal()
        );

        $hydraSchemaFactory = new HydraSchemaFactory($baseSchemaFactory);

        $this->schemaFactory = new SchemaFactory($hydraSchemaFactory);
    }

    public function testBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);

        $this->assertTrue($resultSchema->isDefined());
        $this->assertEquals(str_replace('\\', '.', Dummy::class).'.jsonhal', $resultSchema->getRootDefinitionKey());
    }

    public function testCustomFormatBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'json');

        $this->assertTrue($resultSchema->isDefined());
        $this->assertEquals(str_replace('\\', '.', Dummy::class), $resultSchema->getRootDefinitionKey());
    }

    public function testHasRootDefinitionKeyBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);
        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();

        $this->assertEquals(str_replace('\\', '.', Dummy::class).'.jsonhal', $rootDefinitionKey);
        $this->assertArrayHasKey($rootDefinitionKey, $definitions);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
        $properties = $resultSchema['definitions'][$rootDefinitionKey]['properties'];
        $this->assertArrayHasKey('_links', $properties);
        $this->assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'self' => [
                        'type' => 'object',
                        'properties' => [
                            'href' => [
                                'type' => 'string',
                                'format' => 'iri-reference',
                            ]
                        ]
                    ]
                ],
            ],
            $properties['_links']
        );
    }

    public function testSchemaTypeBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonhal', Schema::TYPE_OUTPUT, OperationType::COLLECTION);
        $definitionName = str_replace('\\', '.', Dummy::class).'.jsonhal';

        $this->assertNull($resultSchema->getRootDefinitionKey());
        $this->assertArrayHasKey('properties', $resultSchema);
        $this->assertArrayHasKey('_embedded', $resultSchema['properties']);
        $this->assertArrayHasKey('totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('itemsPerPage', $resultSchema['properties']);
        $this->assertArrayHasKey('_links', $resultSchema['properties']);
        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayHasKey('_links', $properties);

        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonhal', Schema::TYPE_OUTPUT, null, null, null, null, true);

        $this->assertNull($resultSchema->getRootDefinitionKey());
        $this->assertArrayHasKey('properties', $resultSchema);
        $this->assertArrayHasKey('_embedded', $resultSchema['properties']);
        $this->assertArrayHasKey('totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('itemsPerPage', $resultSchema['properties']);
        $this->assertArrayHasKey('_links', $resultSchema['properties']);
        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayHasKey('_links', $properties);
    }
}
