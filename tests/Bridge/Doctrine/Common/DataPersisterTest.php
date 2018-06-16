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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Common;

use ApiPlatform\Core\Bridge\Doctrine\Common\DataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class DataPersisterTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->assertInstanceOf(DataPersisterInterface::class, new DataPersister($this->prophesize(ManagerRegistry::class)->reveal()));
    }

    public function testSupports(): void
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($this->prophesize(ObjectManager::class)->reveal())->shouldBeCalled();

        $this->assertTrue((new DataPersister($managerRegistryProphecy->reveal()))->supports(new Dummy()));
    }

    public function testDoesNotSupport(): void
    {
        $this->assertFalse((new DataPersister($this->prophesize(ManagerRegistry::class)->reveal()))->supports('dummy'));
    }

    public function testPersist(): void
    {
        $dummy = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->contains($dummy)->willReturn(false);
        $objectManagerProphecy->persist($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManagerProphecy->refresh($dummy)->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        $result = (new DataPersister($managerRegistryProphecy->reveal()))->persist($dummy);
        $this->assertSame($dummy, $result);
    }

    public function testPersistIfEntityAlreadyManaged(): void
    {
        $dummy = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->contains($dummy)->willReturn(true);
        $objectManagerProphecy->persist($dummy)->shouldNotBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManagerProphecy->refresh($dummy)->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        $result = (new DataPersister($managerRegistryProphecy->reveal()))->persist($dummy);
        $this->assertSame($dummy, $result);
    }

    public function testPersistWithNullManager(): void
    {
        $dummy = new Dummy();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $result = (new DataPersister($managerRegistryProphecy->reveal()))->persist($dummy);
        $this->assertSame($dummy, $result);
    }

    public function testRemove(): void
    {
        $dummy = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->remove($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        (new DataPersister($managerRegistryProphecy->reveal()))->remove($dummy);
    }

    public function testRemoveWithNullManager(): void
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        (new DataPersister($managerRegistryProphecy->reveal()))->remove(new Dummy());
    }
}
