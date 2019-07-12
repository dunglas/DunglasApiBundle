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
use ApiPlatform\Core\Bridge\Doctrine\Orm\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionDataProviderTest extends TestCase
{
    public function testGetCollection()
    {
        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([])->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getQuery()->willReturn($queryProphecy->reveal())->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, 'foo', [])->shouldBeCalled();

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal(), $queryExpanderProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testQueryResultExtension()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(EntityRepository::class);
        $repositoryProphecy->createQueryBuilder('o')->willReturn($queryBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($queryBuilder, Argument::type(QueryNameGeneratorInterface::class), Dummy::class, 'foo', [])->shouldBeCalled();
        $extensionProphecy->supportsResult(Dummy::class, 'foo', [])->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($queryBuilder, Dummy::class, 'foo', [])->willReturn([])->shouldBeCalled();

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal(), $queryExpanderProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testCannotCreateQueryBuilder()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository class must have a "createQueryBuilder" method.');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal(), $queryExpanderProphecy->reveal());
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testUnsupportedClass()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(QueryResultCollectionExtensionInterface::class);

        $queryExpanderProphecy = $this->prophesize(QueryExpander::class);

        $dataProvider = new CollectionDataProvider($managerRegistryProphecy->reveal(), $queryExpanderProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(Dummy::class, 'foo'));
    }
}
