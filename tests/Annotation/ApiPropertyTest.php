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

namespace ApiPlatform\Core\Tests\Annotation;

use ApiPlatform\Core\Annotation\ApiProperty;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPropertyTest extends TestCase
{
    public function testAssignation()
    {
        $property = new ApiProperty();
        $property->description = 'description';
        $property->readable = true;
        $property->writable = true;
        $property->readableLink = true;
        $property->writableLink = true;
        $property->required = true;
        $property->iri = 'http://example.com/prop';
        $property->identifier = true;
        $property->attributes = ['foo' => 'bar'];
        $property->example = 'foo';
        $property->schema = ['foo'];
        $property->builtinTypes = ['foo', 'bar'];

        $this->assertEquals('description', $property->description);
        $this->assertTrue($property->readable);
        $this->assertTrue($property->writable);
        $this->assertTrue($property->readableLink);
        $this->assertTrue($property->writableLink);
        $this->assertTrue($property->required);
        $this->assertEquals('http://example.com/prop', $property->iri);
        $this->assertTrue($property->identifier);
        $this->assertEquals(['foo' => 'bar'], $property->attributes);
        $this->assertEquals('foo', $property->example);
        $this->assertEquals(['foo'], $property->schema);
        $this->assertEquals(['foo', 'bar'], $property->builtinTypes);
    }

    public function testConstruct()
    {
        $property = new ApiProperty([
            'deprecationReason' => 'this field is deprecated',
            'fetchable' => true,
            'fetchEager' => false,
            'jsonldContext' => ['foo' => 'bar'],
            'security' => 'is_granted(\'ROLE_ADMIN\')',
            'securityPostDenormalize' => 'is_granted(\'VIEW\', object)',
            'swaggerContext' => ['foo' => 'baz'],
            'openapiContext' => ['foo' => 'baz'],
            'push' => true,
            'attributes' => ['unknown' => 'unknown', 'fetchable' => false],
        ]);
        $this->assertEquals([
            'deprecation_reason' => 'this field is deprecated',
            'fetchable' => false,
            'fetch_eager' => false,
            'jsonld_context' => ['foo' => 'bar'],
            'security' => 'is_granted(\'ROLE_ADMIN\')',
            'security_post_denormalize' => 'is_granted(\'VIEW\', object)',
            'swagger_context' => ['foo' => 'baz'],
            'openapi_context' => ['foo' => 'baz'],
            'push' => true,
            'unknown' => 'unknown',
        ], $property->attributes);
    }

    /**
     * @requires PHP 8.0
     */
    public function testConstructAttribute()
    {
        $property = eval(<<<'PHP'
return new \ApiPlatform\Core\Annotation\ApiProperty(
    deprecationReason: 'this field is deprecated',
    fetchable: true,
    fetchEager: false,
    jsonldContext: ['foo' => 'bar'],
    security: 'is_granted(\'ROLE_ADMIN\')',
    securityPostDenormalize: 'is_granted(\'VIEW\', object)',
    swaggerContext: ['foo' => 'baz'],
    openapiContext: ['foo' => 'baz'],
    push: true,
    attributes: ['unknown' => 'unknown', 'fetchable' => false]
);
PHP
        );

        $this->assertEquals([
            'deprecation_reason' => 'this field is deprecated',
            'fetchable' => false,
            'fetch_eager' => false,
            'jsonld_context' => ['foo' => 'bar'],
            'security' => 'is_granted(\'ROLE_ADMIN\')',
            'security_post_denormalize' => 'is_granted(\'VIEW\', object)',
            'swagger_context' => ['foo' => 'baz'],
            'openapi_context' => ['foo' => 'baz'],
            'push' => true,
            'unknown' => 'unknown',
        ], $property->attributes);
    }
}
