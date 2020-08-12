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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class OrderExtensionTest extends TestCase
{
    public function testApplyToCollectionWithValidOrder()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->addOrderBy('o.name', 'asc')->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc');
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionWithWrongOrder()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->addOrderBy('o.name', 'asc')->shouldNotBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension();
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionWithOrderOverridden()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->addOrderBy('o.foo', 'DESC')->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['foo' => 'DESC']]));

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionWithOrderOverriddenWithNoDirection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilderProphecy->addOrderBy('o.foo', 'ASC')->shouldBeCalled();
        $queryBuilderProphecy->addOrderBy('o.bar', 'DESC')->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['foo', 'bar' => 'DESC']]));

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->shouldBeCalled()->willReturn(['o']);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionOrderByNestedAssociationField(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['author.name']]));

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn(['name']);
        $classMetadataProphecy->hasAssociation('author')->willReturn(true);
        $classMetadataProphecy->hasAssociation('name')->willReturn(false);
        $classMetadataProphecy->getAssociationTargetClass('author')->willReturn(Dummy::class);

        $entityManagerProphecy = $this->prophesize(EntityManager::class);
        $entityManagerProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadataProphecy);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getDQLPart('join')->willReturn(['o' => []]);
        $queryBuilderProphecy->innerJoin('o.author', 'author_a1', null, null)->shouldBeCalled();
        $queryBuilderProphecy->addOrderBy('author_a1.name', 'ASC')->shouldBeCalled();
        $queryBuilderProphecy->getEntityManager()->willReturn($entityManagerProphecy);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);

        $orderExtension = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal());
        $orderExtension->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionOrderByEmbeddedField(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(EmbeddedDummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['embeddedDummy.dummyName' => 'DESC']]));

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn(['id']);
        $classMetadataProphecy->embeddedClasses = ['embeddedDummy' => []];
        $classMetadataProphecy->hasAssociation('embeddedDummy')->willReturn(false);

        $entityManagerProphecy = $this->prophesize(EntityManager::class);
        $entityManagerProphecy->getClassMetadata(EmbeddedDummy::class)->willReturn($classMetadataProphecy);

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->addOrderBy('o.embeddedDummy.dummyName', 'DESC')->shouldBeCalled();
        $queryBuilderProphecy->getEntityManager()->willReturn($entityManagerProphecy);

        $orderExtension = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal());
        $orderExtension->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), EmbeddedDummy::class);
    }
}
