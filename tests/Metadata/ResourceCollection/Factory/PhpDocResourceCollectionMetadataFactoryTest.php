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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\ResourceCollection\Factory\PhpDocResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\ClassWithNoDocBlock;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

class PhpDocResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testExistingDescription()
    {
        $resourceCollection = new ResourceCollection([new Resource(description: 'I am foo')]);
        $decoratedProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceCollectionMetadataFactory($decorated);
        $this->assertSame($resourceCollection[0], $factory->create('Foo')[0]);
    }

    public function testNoDocBlock()
    {
        $resourceCollection = new ResourceCollection([new Resource()]);
        $decoratedProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $decoratedProphecy->create(ClassWithNoDocBlock::class)->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceCollectionMetadataFactory($decorated);
        $this->assertSame($resourceCollection[0], $factory->create(ClassWithNoDocBlock::class)[0]);
    }

    public function testExtractDescription()
    {
        $resourceCollection = new ResourceCollection([new Resource()]);
        $decoratedProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $decoratedProphecy->create(DummyEntity::class)->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceCollectionMetadataFactory($decorated);
        $this->assertSame('My dummy entity.', $factory->create(DummyEntity::class)[0]->description);
    }
}
