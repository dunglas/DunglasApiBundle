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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Cache\QueryExpander;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\SubresourceDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SubresourceDataProviderTest extends TestCase
{
    private function assertIdentifierManagerMethodCalls($managerProphecy)
    {
        $platformProphecy = $this->prophesize(AbstractPlatform::class);

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()->willReturn($platformProphecy);

        $managerProphecy->getConnection()->willReturn($connectionProphecy);
    }

    private function getMetadataProphecies(array $resourceClassesIdentifiers)
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        foreach ($resourceClassesIdentifiers as $resourceClass => $identifiers) {
            $nameCollection = ['foobar'];

            foreach ($identifiers as $identifier) {
                $metadata = new PropertyMetadata();
                $metadata = $metadata->withIdentifier(true);
                $propertyMetadataFactoryProphecy->create($resourceClass, $identifier)->willReturn($metadata);

                $nameCollection[] = $identifier;
            }

            //random property to prevent the use of non-identifiers metadata while looping
            $propertyMetadataFactoryProphecy->create($resourceClass, 'foobar')->willReturn(new PropertyMetadata());

            $propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection($nameCollection));
        }

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal()];
    }

    private function getManagerRegistryProphecy(QueryBuilder $queryBuilder, array $identifiers, string $resourceClass)
    {
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn($identifiers);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getClassMetadata($resourceClass)->willReturn($classMetadataProphecy->reveal());
        $managerProphecy->getRepository($resourceClass)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass($resourceClass)->willReturn($managerProphecy->reveal());

        return $managerRegistryProphecy->reveal();
    }

    public function testNotASubresource()
    {
        $this->expectException(ResourceClassNotSupportedException::class);
        $this->expectExceptionMessage('The given resource class is not a subresource.');

        $identifiers = ['id'];
        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);
        $queryBuilder = $this->prophesize(QueryBuilder::class)->reveal();
        $managerRegistry = $this->getManagerRegistryProphecy($queryBuilder, $identifiers, Dummy::class);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistry, $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal(), []);

        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    public function testGetSubresource()
    {
        $dql = 'SELECT relatedDummies_a2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a1 INNER JOIN id_a1.relatedDummies relatedDummies_a2 WHERE id_a1.id = :id_p1';

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->shouldBeCalled()->willReturn([]);

        $identifiers = ['id'];
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->setParameter('id_p1', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $queryBuilder->getQuery()->shouldBeCalled()->willReturn($queryProphecy->reveal());

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(EntityManager::class);
        $managerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);
        $classMetadataProphecy->getTypeOfField('id')->willReturn(DBALType::INTEGER)->shouldBeCalled();

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a1')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a1.relatedDummies', 'relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a1.id = :id_p1')->shouldBeCalled()->willReturn($qb);
        $qb->getDQL()->shouldBeCalled()->willReturn($dql);

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->in('o', $dql)->willReturn($func)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($exprProphecy->reveal());

        $managerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal());

        $context = ['property' => 'relatedDummies', 'identifiers' => [['id', Dummy::class]], 'collection' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals([], $dataProvider->getSubresource(RelatedDummy::class, ['id' => ['id' => 1]], $context));
    }

    public function testGetSubSubresourceItem()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $identifiers = ['id'];
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        // First manager (Dummy)
        $dummyDQL = 'SELECT relatedDummies_a3 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a2 INNER JOIN id_a2.relatedDummies relatedDummies_a3 WHERE id_a2.id = :id_p2';

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a2')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a2.relatedDummies', 'relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a2.id = :id_p2')->shouldBeCalled()->willReturn($qb);

        $dummyFunc = new Func('in', ['any']);

        $dummyExpProphecy = $this->prophesize(Expr::class);
        $dummyExpProphecy->in('relatedDummies_a1', $dummyDQL)->willReturn($dummyFunc)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($dummyExpProphecy->reveal());

        $qb->getDQL()->shouldBeCalled()->willReturn($dummyDQL);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);
        $classMetadataProphecy->getTypeOfField('id')->willReturn(DBALType::INTEGER)->shouldBeCalled();

        $dummyManagerProphecy = $this->prophesize(EntityManager::class);
        $dummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());
        $dummyManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($dummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($dummyManagerProphecy->reveal());

        // Second manager (RelatedDummy)
        $relatedDQL = 'SELECT IDENTITY(relatedDummies_a1.thirdLevel) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy relatedDummies_a1 WHERE relatedDummies_a1.id = :id_p1 AND relatedDummies_a1 IN(SELECT relatedDummies_a3 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a2 INNER JOIN id_a2.relatedDummies relatedDummies_a3 WHERE id_a2.id = :id_p2)';

        $rqb = $this->prophesize(QueryBuilder::class);
        $rqb->select('IDENTITY(relatedDummies_a1.thirdLevel)')->shouldBeCalled()->willReturn($rqb);
        $rqb->from(RelatedDummy::class, 'relatedDummies_a1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere('relatedDummies_a1.id = :id_p1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere($dummyFunc)->shouldBeCalled()->willReturn($rqb);
        $rqb->getDQL()->shouldBeCalled()->willReturn($relatedDQL);

        $relatedExpProphecy = $this->prophesize(Expr::class);
        $relatedExpProphecy->in('o', $relatedDQL)->willReturn($func)->shouldBeCalled();

        $rqb->expr()->shouldBeCalled()->willReturn($relatedExpProphecy->reveal());

        $rClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $rClassMetadataProphecy->hasAssociation('thirdLevel')->shouldBeCalled()->willReturn(true);
        $rClassMetadataProphecy->getAssociationMapping('thirdLevel')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_ONE]);
        $rClassMetadataProphecy->getTypeOfField('id')->willReturn(DBALType::INTEGER)->shouldBeCalled();

        $rDummyManagerProphecy = $this->prophesize(EntityManager::class);
        $rDummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($rqb->reveal());
        $rDummyManagerProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($rClassMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($rDummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($rDummyManagerProphecy->reveal());

        $result = new \stdClass();
        // Origin manager (ThirdLevel)
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->shouldBeCalled()->willReturn($result);

        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $queryBuilder->getQuery()->shouldBeCalled()->willReturn($queryProphecy->reveal());
        $queryBuilder->setParameter('id_p1', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);
        $queryBuilder->setParameter('id_p2', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(ThirdLevel::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy->getManagerForClass(ThirdLevel::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers, RelatedDummy::class => $identifiers]);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal());

        $context = ['property' => 'thirdLevel', 'identifiers' => [['id', Dummy::class], ['relatedDummies', RelatedDummy::class]], 'collection' => false, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals($result, $dataProvider->getSubresource(ThirdLevel::class, ['id' => ['id' => 1], 'relatedDummies' => ['id' => 1]], $context));
    }

    public function testGetSubresourceOneToOneOwningRelation()
    {
        // RelatedOwningDummy OneToOne Dummy
        $dql = 'SELECT ownedDummy_a2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a1 INNER JOIN id_a1.ownedDummy ownedDummy_a2 WHERE id_a1.id = :id_p1';

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->shouldBeCalled()->willReturn([]);

        $identifiers = ['id'];
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->setParameter('id_p1', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();
        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);
        $queryBuilder->getQuery()->shouldBeCalled()->willReturn($queryProphecy->reveal());

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(EntityManager::class);
        $managerProphecy->getRepository(RelatedOwningDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('ownedDummy')->willReturn(true)->shouldBeCalled();
        $classMetadataProphecy->getAssociationMapping('ownedDummy')->shouldBeCalled()->willReturn(['type' => ClassMetadata::ONE_TO_ONE]);
        $classMetadataProphecy->getTypeOfField('id')->willReturn(DBALType::INTEGER)->shouldBeCalled();

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('IDENTITY(id_a1.ownedDummy)')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a1')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a1.id = :id_p1')->shouldBeCalled()->willReturn($qb);
        $qb->getDQL()->shouldBeCalled()->willReturn($dql);

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->in('o', $dql)->willReturn($func)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($exprProphecy->reveal());

        $managerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedOwningDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal());

        $context = ['property' => 'ownedDummy', 'identifiers' => [['id', Dummy::class]], 'collection' => false, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals([], $dataProvider->getSubresource(RelatedOwningDummy::class, ['id' => ['id' => 1]], $context));
    }

    public function testQueryResultExtension()
    {
        $dql = 'SELECT relatedDummies_a2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a1 INNER JOIN id_a1.relatedDummies relatedDummies_a2 WHERE id_a1.id = :id_p1';

        $identifiers = ['id'];
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->setParameter('id_p1', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(EntityManager::class);
        $managerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);
        $classMetadataProphecy->getTypeOfField('id')->willReturn(DBALType::INTEGER)->shouldBeCalled();

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a1')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a1.relatedDummies', 'relatedDummies_a2')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a1.id = :id_p1')->shouldBeCalled()->willReturn($qb);
        $qb->getDQL()->shouldBeCalled()->willReturn($dql);

        $exprProphecy = $this->prophesize(Expr::class);
        $exprProphecy->in('o', $dql)->willReturn($func)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($exprProphecy->reveal());

        $managerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());
        $this->assertIdentifierManagerMethodCalls($managerProphecy);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), RelatedDummy::class, null, Argument::type('array'))->shouldBeCalled();
        $extensionProphecy->supportsResult(RelatedDummy::class, null, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder, RelatedDummy::class, null, Argument::type('array'))->willReturn([])->shouldBeCalled();

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal(), [$extensionProphecy->reveal()]);

        $context = ['property' => 'relatedDummies', 'identifiers' => [['id', Dummy::class]], 'collection' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals([], $dataProvider->getSubresource(RelatedDummy::class, ['id' => ['id' => 1]], $context));
    }

    public function testCannotCreateQueryBuilder()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository class must have a "createQueryBuilder" method.');

        $identifiers = ['id'];
        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal());
        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    public function testThrowResourceClassNotSupportedException()
    {
        $this->expectException(ResourceClassNotSupportedException::class);

        $identifiers = ['id'];
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal());
        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    /**
     * @group legacy
     */
    public function testGetSubSubresourceItemLegacy()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $identifiers = ['id'];
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        // First manager (Dummy)
        $dummyDQL = 'SELECT relatedDummies_a3 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a2 INNER JOIN id_a2.relatedDummies relatedDummies_a3 WHERE id_a2.id = :id_p2';

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a2')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a2.relatedDummies', 'relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a2.id = :id_p2')->shouldBeCalled()->willReturn($qb);

        $dummyFunc = new Func('in', ['any']);

        $dummyExpProphecy = $this->prophesize(Expr::class);
        $dummyExpProphecy->in('relatedDummies_a1', $dummyDQL)->willReturn($dummyFunc)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($dummyExpProphecy->reveal());

        $qb->getDQL()->shouldBeCalled()->willReturn($dummyDQL);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn($identifiers);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);
        $classMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn(DBALType::INTEGER);

        $dummyManagerProphecy = $this->prophesize(EntityManager::class);
        $dummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());
        $dummyManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($dummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($dummyManagerProphecy->reveal());

        // Second manager (RelatedDummy)
        $relatedDQL = 'SELECT IDENTITY(relatedDummies_a1.thirdLevel) FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy relatedDummies_a1 WHERE relatedDummies_a1.id = :id_p1 AND relatedDummies_a1 IN(SELECT relatedDummies_a3 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy id_a2 INNER JOIN id_a2.relatedDummies relatedDummies_a3 WHERE id_a2.id = :id_p2)';

        $rqb = $this->prophesize(QueryBuilder::class);
        $rqb->select('IDENTITY(relatedDummies_a1.thirdLevel)')->shouldBeCalled()->willReturn($rqb);
        $rqb->from(RelatedDummy::class, 'relatedDummies_a1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere('relatedDummies_a1.id = :id_p1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere($dummyFunc)->shouldBeCalled()->willReturn($rqb);
        $rqb->getDQL()->shouldBeCalled()->willReturn($relatedDQL);

        $relatedExpProphecy = $this->prophesize(Expr::class);
        $relatedExpProphecy->in('o', $relatedDQL)->willReturn($func)->shouldBeCalled();

        $rqb->expr()->shouldBeCalled()->willReturn($relatedExpProphecy->reveal());

        $rClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $rClassMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn($identifiers);
        $rClassMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn(DBALType::INTEGER);
        $rClassMetadataProphecy->hasAssociation('thirdLevel')->shouldBeCalled()->willReturn(true);
        $rClassMetadataProphecy->getAssociationMapping('thirdLevel')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_ONE]);

        $rDummyManagerProphecy = $this->prophesize(EntityManager::class);
        $rDummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($rqb->reveal());
        $rDummyManagerProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($rClassMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($rDummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($rDummyManagerProphecy->reveal());

        $result = new \stdClass();
        // Origin manager (ThirdLevel)
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->shouldBeCalled()->willReturn($result);

        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $queryBuilder->getQuery()->shouldBeCalled()->willReturn($queryProphecy->reveal());
        $queryBuilder->setParameter('id_p1', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);
        $queryBuilder->setParameter('id_p2', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(ThirdLevel::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy->getManagerForClass(ThirdLevel::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers, RelatedDummy::class => $identifiers]);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal());

        $context = ['property' => 'thirdLevel', 'identifiers' => [['id', Dummy::class], ['relatedDummies', RelatedDummy::class]], 'collection' => false];

        $this->assertEquals($result, $dataProvider->getSubresource(ThirdLevel::class, ['id' => 1, 'relatedDummies' => 1], $context));
    }

    public function testGetSubresourceCollectionItem()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $identifiers = ['id'];
        $funcProphecy = $this->prophesize(Func::class);
        $func = $funcProphecy->reveal();

        // First manager (Dummy)
        $dummyDQL = 'dql';

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select('relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->from(Dummy::class, 'id_a2')->shouldBeCalled()->willReturn($qb);
        $qb->innerJoin('id_a2.relatedDummies', 'relatedDummies_a3')->shouldBeCalled()->willReturn($qb);
        $qb->andWhere('id_a2.id = :id_p2')->shouldBeCalled()->willReturn($qb);

        $dummyFunc = new Func('in', ['any']);

        $dummyExpProphecy = $this->prophesize(Expr::class);
        $dummyExpProphecy->in('relatedDummies_a1', $dummyDQL)->willReturn($dummyFunc)->shouldBeCalled();

        $qb->expr()->shouldBeCalled()->willReturn($dummyExpProphecy->reveal());

        $qb->getDQL()->shouldBeCalled()->willReturn($dummyDQL);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();
        $classMetadataProphecy->getAssociationMapping('relatedDummies')->shouldBeCalled()->willReturn(['type' => ClassMetadata::MANY_TO_MANY]);
        $classMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn(DBALType::INTEGER);

        $dummyManagerProphecy = $this->prophesize(EntityManager::class);
        $dummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($qb->reveal());
        $dummyManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($dummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($dummyManagerProphecy->reveal());

        // Second manager (RelatedDummy)
        $relatedDQL = 'relateddql';

        $rqb = $this->prophesize(QueryBuilder::class);
        $rqb->select('relatedDummies_a1')->shouldBeCalled()->willReturn($rqb);
        $rqb->from(RelatedDummy::class, 'relatedDummies_a1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere('relatedDummies_a1.id = :id_p1')->shouldBeCalled()->willReturn($rqb);
        $rqb->andWhere($dummyFunc)->shouldBeCalled()->willReturn($rqb);
        $rqb->getDQL()->shouldBeCalled()->willReturn($relatedDQL);

        $relatedExpProphecy = $this->prophesize(Expr::class);
        $relatedExpProphecy->in('o', $relatedDQL)->willReturn($func)->shouldBeCalled();

        $rqb->expr()->shouldBeCalled()->willReturn($relatedExpProphecy->reveal());

        $rClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $rClassMetadataProphecy->hasAssociation('id')->shouldBeCalled()->willReturn(false);
        $rClassMetadataProphecy->isIdentifier('id')->shouldBeCalled()->willReturn(true);
        $rClassMetadataProphecy->getTypeOfField('id')->shouldBeCalled()->willReturn(DBALType::INTEGER);

        $rDummyManagerProphecy = $this->prophesize(EntityManager::class);
        $rDummyManagerProphecy->createQueryBuilder()->shouldBeCalled()->willReturn($rqb->reveal());
        $rDummyManagerProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($rClassMetadataProphecy->reveal());
        $this->assertIdentifierManagerMethodCalls($rDummyManagerProphecy);

        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($rDummyManagerProphecy->reveal());

        $result = new \stdClass();
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getOneOrNullResult()->shouldBeCalled()->willReturn($result);

        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $queryBuilder->andWhere($func)->shouldBeCalled()->willReturn($queryBuilder);

        $queryBuilder->getQuery()->shouldBeCalled()->willReturn($queryProphecy->reveal());
        $queryBuilder->setParameter('id_p1', 2, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);
        $queryBuilder->setParameter('id_p2', 1, DBALType::INTEGER)->shouldBeCalled()->willReturn($queryBuilder);

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->shouldBeCalled()->willReturn($queryBuilder->reveal());

        $rDummyManagerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers, RelatedDummy::class => $identifiers]);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, $queryExpanderProphecy->reveal());

        $context = ['property' => 'id', 'identifiers' => [['id', Dummy::class, true], ['relatedDummies', RelatedDummy::class, true]], 'collection' => false, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals($result, $dataProvider->getSubresource(RelatedDummy::class, ['id' => ['id' => 1], 'relatedDummies' => ['id' => 2]], $context));
    }
}
