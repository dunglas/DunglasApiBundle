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

namespace ApiPlatform\Core\Tests\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PropertyMetadataTest extends TestCase
{
    public function testValueObject()
    {
        $type = new Type(Type::BUILTIN_TYPE_STRING);
        $metadata = new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'desc', true, true, false, false, true, false, 'http://example.com/foo', null, ['foo' => 'bar']);
        $this->assertEquals($type, $metadata->getType());
        $this->assertEquals('desc', $metadata->getDescription());
        $this->assertTrue($metadata->isReadable());
        $this->assertTrue($metadata->isWritable());
        $this->assertFalse($metadata->isReadableLink());
        $this->assertFalse($metadata->isWritableLink());
        $this->assertTrue($metadata->isRequired());
        $this->assertFalse($metadata->isIdentifier());
        $this->assertEquals('http://example.com/foo', $metadata->getIri());
        $this->assertEquals(['foo' => 'bar'], $metadata->getAttributes());

        $newType = new Type(Type::BUILTIN_TYPE_BOOL);
        $newMetadata = $metadata->withType($newType);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals($newType, $newMetadata->getType());

        $newMetadata = $metadata->withDescription('description');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals('description', $newMetadata->getDescription());

        $newMetadata = $metadata->withReadable(false);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertFalse($newMetadata->isReadable());

        $newMetadata = $metadata->withWritable(false);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertFalse($newMetadata->isWritable());

        $newMetadata = $metadata->withReadableLink(true);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertTrue($newMetadata->isReadableLink());

        $newMetadata = $metadata->withWritableLink(true);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertTrue($newMetadata->isWritableLink());

        $newMetadata = $metadata->withRequired(false);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertFalse($newMetadata->isRequired());

        $newMetadata = $metadata->withIdentifier(true);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertTrue($newMetadata->isIdentifier());

        $newMetadata = $metadata->withIri('foo:bar');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals('foo:bar', $newMetadata->getIri());

        $newMetadata = $metadata->withAttributes(['a' => 'b']);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals(['a' => 'b'], $newMetadata->getAttributes());
        $this->assertEquals('b', $newMetadata->getAttribute('a'));

        $newMetadata = $metadata->withInitializable(true);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertTrue($newMetadata->isInitializable());

        $newMetadata = $metadata->withDefault('foobar');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals('foobar', $newMetadata->getDefault());

        $newMetadata = $metadata->withExample('foobarexample');
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals('foobarexample', $newMetadata->getExample());

        $newMetadata = $metadata->withSchema(['foobarexample']);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals(['foobarexample'], $newMetadata->getSchema());

        $newMetadata = $metadata->withBuiltinTypes(['footype', 'bartype']);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals(['footype', 'bartype'], $newMetadata->getBuiltinTypes());

        $newMetadata = $metadata->withTypes(['foo', 'bar']);
        $this->assertNotSame($metadata, $newMetadata);
        $this->assertEquals(['foo', 'bar'], $newMetadata->getTypes());
    }

    public function testShouldReturnRequiredFalseWhenRequiredTrueIsSetButMaskedByWritableFalse()
    {
        $metadata = new PropertyMetadata();

        $metadata = $metadata->withRequired(true);
        $metadata = $metadata->withWritable(false);

        $this->assertFalse($metadata->isRequired());
    }

    public function testShouldReturnPreviouslySetRequiredTrueWhenWritableFalseUnmasked()
    {
        $metadata = new PropertyMetadata();

        $metadata = $metadata->withRequired(true);
        $metadata = $metadata->withWritable(false);
        $metadata = $metadata->withWritable(true);

        $this->assertTrue($metadata->isRequired());
    }
}
