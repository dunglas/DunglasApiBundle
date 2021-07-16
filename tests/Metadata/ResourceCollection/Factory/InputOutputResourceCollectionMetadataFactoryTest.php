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

namespace ApiPlatform\Core\Tests\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Metadata\ResourceCollection\Factory\InputOutputResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

class InputOutputResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider getAttributes
     */
    public function testInputOutputMetadata($input, $expected)
    {
        $resourceCollection = new ResourceCollection([new Resource(input: $input)]);
        $decoratedProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new InputOutputResourceCollectionMetadataFactory($decorated);
        $this->assertSame($expected, $factory->create('Foo')[0]->input);
    }

    /**
     * @dataProvider getAttributes
     * TODO: GraphQl operations as object?
     */
    // public function testInputOutputViaGraphQlMetadata($attributes, $expected)
    // {
    //     $resourceCollection = new ResourceCollection([new Resource(graphQl: ['create' => ['input' => $attributes]])]);
    //     $decoratedProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
    //     $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
    //     $decorated = $decoratedProphecy->reveal();
    //
    //     $factory = new InputOutputResourceCollectionMetadataFactory($decorated);
    //     $this->assertSame($expected, $factory->create('Foo')[0]->graphQl['create']['input']);
    // }

    public function getAttributes(): array
    {
        return [
            // no input class defined
            [[], null],
            // input is a string
            [DummyEntity::class, ['class' => DummyEntity::class, 'name' => 'DummyEntity']],
            // input is false
            [false, ['class' => null]],
            // input is an array
            [['class' => DummyEntity::class, 'type' => 'Foo'], ['class' => DummyEntity::class, 'type' => 'Foo', 'name' => 'DummyEntity']],
        ];
    }
}
