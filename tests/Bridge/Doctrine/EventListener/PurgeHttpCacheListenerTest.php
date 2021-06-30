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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Doctrine\EventListener\PurgeHttpCacheListener;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\HttpCache\PurgerInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyNoGetOperation;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PurgeHttpCacheListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testOnFlush()
    {
        $toInsert1 = new Dummy();
        $toInsert2 = new Dummy();

        $toUpdate1 = new Dummy();
        $toUpdate1->setId(1);
        $toUpdate2 = new Dummy();
        $toUpdate2->setId(2);

        $toDelete1 = new Dummy();
        $toDelete1->setId(3);
        $toDelete2 = new Dummy();
        $toDelete2->setId(4);

        $toDeleteNoPurge = new DummyNoGetOperation();
        $toDeleteNoPurge->setId(5);

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/dummies', '/dummies/1', '/dummies/2', '/dummies/3', '/dummies/4'])->shouldBeCalled();

        $xKeyPurgerProphecy = $this->prophesize(PurgerInterface::class);
        $xKeyPurgerProphecy->purge(['/dummies', '/dummies/1', '/dummies/2', '/dummies/3', '/dummies/4'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResourceClass(DummyNoGetOperation::class)->willThrow(new InvalidArgumentException())->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toUpdate1)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toUpdate2)->willReturn('/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete1)->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete2)->willReturn('/dummies/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteNoPurge)->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyNoGetOperation::class))->willReturn(DummyNoGetOperation::class)->shouldBeCalled();

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert1, $toInsert2])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([$toUpdate1, $toUpdate2])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete1, $toDelete2, $toDeleteNoPurge])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $dummyClassMetadata = new ClassMetadata(Dummy::class);
        $dummyClassMetadata->associationMappings = [
            'relatedDummy' => [],
            'relatedOwningDummy' => [],
        ];
        $emProphecy->getClassMetadata(Dummy::class)->willReturn($dummyClassMetadata)->shouldBeCalled();
        $emProphecy->getClassMetadata(DummyNoGetOperation::class)->willReturn(new ClassMetadata(DummyNoGetOperation::class))->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->isReadable(Argument::type(Dummy::class), 'relatedDummy')->willReturn(true);
        $propertyAccessorProphecy->isReadable(Argument::type(Dummy::class), 'relatedOwningDummy')->willReturn(false);
        $propertyAccessorProphecy->getValue(Argument::type(Dummy::class), 'relatedDummy')->shouldBeCalled();
        $propertyAccessorProphecy->getValue(Argument::type(Dummy::class), 'relatedOwningDummy')->shouldNotBeCalled();

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal(), $xKeyPurgerProphecy->reveal(), true, true);
        $listener->onFlush($eventArgs);
        $listener->postFlush();
    }

    public function testPreUpdate()
    {
        $oldRelatedDummy = new RelatedDummy();
        $oldRelatedDummy->setId(1);

        $newRelatedDummy = new RelatedDummy();
        $newRelatedDummy->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/dummies', '/dummies/1', '/related_dummies/old', '/related_dummies/new'])->shouldBeCalled();

        $xKeyPurgerProphecy = $this->prophesize(PurgerInterface::class);
        $xKeyPurgerProphecy->purge(['/dummies', '/dummies/1', '/related_dummies/old', '/related_dummies/new'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($oldRelatedDummy)->willReturn('/related_dummies/old')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($newRelatedDummy)->willReturn('/related_dummies/new')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class)->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->mapManyToOne(['fieldName' => 'relatedDummy', 'targetEntity' => RelatedDummy::class]);
        $emProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadata)->shouldBeCalled();

        $changeSet = ['relatedDummy' => [$oldRelatedDummy, $newRelatedDummy]];
        $eventArgs = new PreUpdateEventArgs($dummy, $emProphecy->reveal(), $changeSet);

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), null, $xKeyPurgerProphecy->reveal(), true, true);
        $listener->preUpdate($eventArgs);
        $listener->postFlush();
    }

    public function testNothingToPurge()
    {
        $dummyNoGetOperation = new DummyNoGetOperation();
        $dummyNoGetOperation->setId(1);

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge([])->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(DummyNoGetOperation::class)->willThrow(new InvalidArgumentException())->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($dummyNoGetOperation)->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyNoGetOperation::class))->willReturn(DummyNoGetOperation::class)->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(DummyNoGetOperation::class);
        $emProphecy->getClassMetadata(DummyNoGetOperation::class)->willReturn($classMetadata)->shouldBeCalled();

        $changeSet = ['lorem' => 'ipsum'];
        $eventArgs = new PreUpdateEventArgs($dummyNoGetOperation, $emProphecy->reveal(), $changeSet);

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $listener->preUpdate($eventArgs);
        $listener->postFlush();
    }
}
