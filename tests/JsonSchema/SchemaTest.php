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

use ApiPlatform\Core\JsonSchema\Schema;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    /**
     * @dataProvider versionProvider
     */
    public function testJsonSchemaVersion(string $version, string $ref): void
    {
        $schema = new Schema($version);
        $schema['$ref'] = $ref;

        $this->assertInstanceOf(\ArrayObject::class, $schema);
        $this->assertSame($version, $schema->getVersion());
        $this->assertSame('Foo', $schema->getRootDefinitionKey());
    }

    /**
     * @dataProvider versionProvider
     */
    public function testCollectionJsonSchemaVersion(string $version, string $ref): void
    {
        $schema = new Schema($version);
        $schema['items']['$ref'] = $ref;

        $this->assertInstanceOf(\ArrayObject::class, $schema);
        $this->assertSame($version, $schema->getVersion());
        $this->assertSame('Foo', $schema->getItemsDefinitionKey());
    }

    public function versionProvider(): iterable
    {
        yield [Schema::VERSION_JSON_SCHEMA, '#/definitions/Foo'];
        yield [Schema::VERSION_SWAGGER, '#/definitions/Foo'];
        yield [Schema::VERSION_OPENAPI, '#/components/schemas/Foo'];
    }

    public function testNoRootDefinitionKey(): void
    {
        $this->assertNull((new Schema())->getRootDefinitionKey());
    }

    public function testContainsJsonSchemaVersion(): void
    {
        $schema = new Schema();
        $this->assertSame('http://json-schema.org/draft-07/schema#', $schema['$schema']);
    }

    /**
     * @dataProvider definitionsDataProvider
     */
    public function testDefinitions(string $version, array $baseDefinitions): void
    {
        $schema = new Schema($version);
        $schema->setDefinitions(new \ArrayObject($baseDefinitions));

        if (Schema::VERSION_OPENAPI === $version) {
            $this->assertArrayHasKey('schemas', $schema['components']);
        } else {
            $this->assertArrayHasKey('definitions', $schema->getArrayCopy(true));
        }

        $definitions = $schema->getDefinitions();
        $this->assertArrayHasKey('foo', $definitions->getArrayCopy());

        $this->assertArrayNotHasKey('definitions', $schema->getArrayCopy(false));
        $this->assertArrayNotHasKey('components', $schema->getArrayCopy(false));
    }

    public function definitionsDataProvider(): iterable
    {
        yield [Schema::VERSION_OPENAPI,  ['foo' => []]];
        yield [Schema::VERSION_JSON_SCHEMA,  ['foo' => []]];
    }

    public function testIsDefined(): void
    {
        $this->assertFalse((new Schema())->isDefined());

        $schema = new Schema();
        $schema['$ref'] = 'foo';
        $this->assertTrue($schema->isDefined());

        $schema = new Schema();
        $schema['type'] = 'object';
        $this->assertTrue($schema->isDefined());
    }
}
