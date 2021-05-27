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

namespace ApiPlatform\Core\Tests\Core\Metadata;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

class AttributeTraitTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testExtraProperties()
    {
        /** @var \stdClass */
        $resource = new Resource();
        $resource->foo = 'bar';
        $this->assertEquals(['foo' => 'bar'], $resource->extraProperties);
        $this->assertEquals($resource->foo, 'bar');
    }

    /**
     * @requires PHP 8.0
     */
    public function testNamedArguments()
    {
        /** @var \stdClass */
        $resource = new Resource(types: ['Resource'], foo: 'bar');
        $this->assertEquals($resource->types, ['Resource']);
        $this->assertEquals($resource->foo, 'bar');
        $this->assertEquals(['foo' => 'bar'], $resource->extraProperties);
    }
}
