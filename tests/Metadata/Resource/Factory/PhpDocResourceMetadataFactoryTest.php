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

use ApiPlatform\Core\Metadata\Resource\Factory\PhpDocResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\OperationCollectionMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\ClassWithNoDocBlock;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

class PhpDocResourceMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testExistingDescription()
    {
        $resourceMetadata = new ResourceMetadata([new OperationCollectionMetadata('/dummies', null, 'My desc')]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataFactory($decorated);
        $this->assertSame($resourceMetadata, $factory->create('Foo'));
    }

    public function testNoDocBlock()
    {
        $resourceMetadata = new ResourceMetadata([new OperationCollectionMetadata('/dummies')]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(ClassWithNoDocBlock::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataFactory($decorated);
        $this->assertSame($resourceMetadata, $factory->create(ClassWithNoDocBlock::class));
    }

    public function testExtractDescription()
    {
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(DummyEntity::class)->willReturn(new ResourceMetadata([new OperationCollectionMetadata('/dummies')]))->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataFactory($decorated);
        $this->assertSame('My dummy entity.', $factory->create(DummyEntity::class)->getDescription());
    }
}
