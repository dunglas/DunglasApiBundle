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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AbstractDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConcreteDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\UnknownDummy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class EagerLoadingExtensionTest extends TestCase
{
    public function testApplyToCollection()
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo']];
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'notindatabase', 'notreadable', 'embeddedDummy']);
        $relatedEmbedableCollection = new PropertyNameCollection(['name']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(EmbeddableDummy::class)->willReturn($relatedEmbedableCollection)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $embeddedPropertyMetadata = new PropertyMetadata();
        $embeddedPropertyMetadata = $embeddedPropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new PropertyMetadata();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new PropertyMetadata();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', $callContext)->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', $callContext)->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'embeddedDummy', $callContext)->willReturn($embeddedPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase', $callContext)->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable', $callContext)->willReturn($notReadablePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn('notindatabase' !== $property)->shouldBeCalled();
            }
        }
        $relatedClassMetadataProphecy->hasField('embeddedDummy.name')->willReturn(true)->shouldBeCalled();

        $relatedClassMetadataProphecy->embeddedClasses = ['embeddedDummy' => ['class' => EmbeddableDummy::class]];

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name,embeddedDummy.name}')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy2_a2.{id,name,embeddedDummy.name}')->shouldBeCalledTimes(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, null, $context);
    }

    public function testApplyToItem()
    {
        $context = ['groups' => ['foo']];
        $callContext = ['serializer_groups' => ['foo']];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'embeddedDummy', 'notindatabase', 'notreadable', 'relation']);
        $relatedEmbedableCollection = new PropertyNameCollection(['name']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(EmbeddableDummy::class)->willReturn($relatedEmbedableCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy3', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy4', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy5', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'singleInheritanceRelation', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $embeddedDummyPropertyMetadata = new PropertyMetadata();
        $embeddedDummyPropertyMetadata = $embeddedDummyPropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new PropertyMetadata();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new PropertyMetadata();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', $callContext)->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', $callContext)->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'embeddedDummy', $callContext)->willReturn($embeddedDummyPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase', $callContext)->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable', $callContext)->willReturn($notReadablePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'relation', $callContext)->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(UnknownDummy::class, 'id', $callContext)->willReturn($idPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy3' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinTable' => ['joinColumns' => [['nullable' => false]]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy4' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'targetEntity' => UnknownDummy::class],
            'relatedDummy5' => ['fetch' => ClassMetadataInfo::FETCH_LAZY, 'targetEntity' => UnknownDummy::class],
            'singleInheritanceRelation' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'targetEntity' => AbstractDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property && 'embeddedDummy' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn('notindatabase' !== $property)->shouldBeCalled();
            }
        }
        $relatedClassMetadataProphecy->hasField('embeddedDummy.name')->willReturn(true)->shouldBeCalled();

        $relatedClassMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
        ];

        $relatedClassMetadataProphecy->embeddedClasses = ['embeddedDummy' => ['class' => EmbeddableDummy::class]];

        $singleInheritanceClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $singleInheritanceClassMetadataProphecy->subClasses = [ConcreteDummy::class];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(AbstractDummy::class)->shouldBeCalled()->willReturn($singleInheritanceClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);
        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->leftJoin('relatedDummy_a1.relation', 'relation_a2')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a3')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->leftJoin('o.relatedDummy3', 'relatedDummy3_a4')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->leftJoin('o.relatedDummy4', 'relatedDummy4_a5')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->leftJoin('o.singleInheritanceRelation', 'singleInheritanceRelation_a6')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name,embeddedDummy.name}')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relation_a2.{id}')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy2_a3.{id}')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy3_a4.{id}')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy4_a5.{id}')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('singleInheritanceRelation_a6')->shouldBeCalledTimes(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true);

        $orderExtensionTest->applyToItem($queryBuilder, new QueryNameGenerator(), Dummy::class, [], null, $context);
    }

    public function testCreateItemWithOperationName()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['item_operation_name' => 'item_operation', 'serializer_groups' => ['foo']])->shouldBeCalled()->willReturn(new PropertyMetadata());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], 'item_operation', ['groups' => ['foo']]);
    }

    public function testCreateCollectionWithOperationName()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['collection_operation_name' => 'collection_operation', 'serializer_groups' => ['foo']])->shouldBeCalled()->willReturn(new PropertyMetadata());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, 'collection_operation', ['groups' => ['foo']]);
    }

    public function testDenormalizeItemWithCorrectResourceClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        //Dummy is the correct class for the denormalization context serialization groups, and we're fetching RelatedDummy
        $resourceMetadata = (new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo']]]);
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], 'item_operation', ['resource_class' => Dummy::class]);
    }

    public function testDenormalizeItemWithExistingGroups()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        //groups exist from the context, we don't need to compute them again
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldNotBeCalled();
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], 'item_operation', [AbstractNormalizer::GROUPS => 'some_groups']);
    }

    public function testMaxJoinsReached()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary with the "api_platform.eager_loading.max_joins" configuration key (https://api-platform.com/docs/core/performance/#eager-loading), or limit the maximum serialization depth using the "enable_max_depth" option of the Symfony serializer (https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth).');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['dummy']);
        $dummyNameCollection = new PropertyNameCollection(['relatedDummy']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $relatedPropertyMetadata = new PropertyMetadata();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'dummy', ['serializer_groups' => ['foo']])->willReturn($relatedPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [
            'dummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => Dummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->innerJoin(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $queryBuilderProphecy->addSelect(Argument::type('string'))->shouldBeCalled();

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, null, ['groups' => ['foo']]);
    }

    public function testMaxDepth()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['normalization_context' => ['enable_max_depth' => 'true', 'groups' => ['foo']]]);

        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['dummy']);
        $dummyNameCollection = new PropertyNameCollection(['relatedDummy']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $relatedPropertyMetadata = new PropertyMetadata();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'dummy', ['serializer_groups' => ['foo']])->willReturn($relatedPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [
            'dummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => Dummy::class],
        ];

        $dummyClassMetadataInterfaceProphecy = $this->prophesize(ClassMetadataInterface::class);
        $relatedClassMetadataInterfaceProphecy = $this->prophesize(ClassMetadataInterface::class);
        $classMetadataFactoryProphecy = $this->prophesize(ClassMetadataFactoryInterface::class);

        $dummyAttributeMetadata = new AttributeMetadata('dummy');
        $dummyAttributeMetadata->setMaxDepth(2);

        $relatedAttributeMetadata = new AttributeMetadata('relatedDummy');
        $relatedAttributeMetadata->setMaxDepth(4);

        $dummyClassMetadataInterfaceProphecy->getAttributesMetadata()->willReturn(['relatedDummy' => $dummyAttributeMetadata]);
        $relatedClassMetadataInterfaceProphecy->getAttributesMetadata()->willReturn(['dummy' => $relatedAttributeMetadata]);

        $classMetadataFactoryProphecy->getMetadataFor(RelatedDummy::class)->willReturn($relatedClassMetadataInterfaceProphecy->reveal());
        $classMetadataFactoryProphecy->getMetadataFor(Dummy::class)->willReturn($dummyClassMetadataInterfaceProphecy->reveal());

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->innerJoin(Argument::type('string'), Argument::type('string'))->shouldBeCalledTimes(2);
        $queryBuilderProphecy->addSelect(Argument::type('string'))->shouldBeCalled();

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, null, null, true, $classMetadataFactoryProphecy->reveal());
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class);
    }

    public function testForceEager()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['normalization_context' => [AbstractNormalizer::GROUPS => 'foobar']]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);

        $propertyMetadataFactoryProphecy->create(UnknownDummy::class, 'id', ['serializer_groups' => ['foobar']])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foobar']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadataInfo::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy->innerJoin('o.relation', 'relation_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relation_a1.{id}')->shouldBeCalledTimes(1);

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true, null, null, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testExtraLazy()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['normalization_context' => [AbstractNormalizer::GROUPS => 'foobar']]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        //$propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foobar']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadataInfo::FETCH_EXTRA_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true, null, null, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testResourceClassNotFoundException()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo']]]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foo']])->willThrow(new ResourceClassNotFoundException());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadataInfo::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true, null, null, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testPropertyNotFoundException()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo']]]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foo']])->willThrow(new PropertyNotFoundException());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadataInfo::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true, null, null, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testResourceClassNotFoundExceptionPropertyNameCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo']]]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willThrow(new ResourceClassNotFoundException());

        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => ClassMetadataInfo::FETCH_LAZY, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);
        $queryBuilderProphecy->innerJoin('o.relation', 'relation_a1')->shouldBeCalledTimes(1);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true, null, null, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyToCollectionWithSerializerContextBuilder(): void
    {
        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true)->shouldBeCalled()->willReturn([AbstractNormalizer::GROUPS => ['foo']]);

        $this->doTestApplyToCollectionWithSerializerContext($serializerContextBuilderProphecy->reveal());
    }

    private function doTestApplyToCollectionWithSerializerContext($serializerContextFactory): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', ['serializer_groups' => ['foo']])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', ['serializer_groups' => ['foo']])->willReturn($namePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn(true)->shouldBeCalled();
            }
        }

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name}')->shouldBeCalledTimes(1);

        $request = Request::create('/api/dummies', 'GET', []);
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->attributes->set('_api_collection_operation_name', 'get');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false, $requestStack, $serializerContextFactory, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    /**
     * @group legacy
     */
    public function testLegacyAttributes(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $request = Request::create('/api/dummies', 'GET', []);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest($request, true)->shouldBeCalled()->willReturn([AbstractNormalizer::GROUPS => ['foo'], AbstractNormalizer::ATTRIBUTES => ['relatedDummy' => ['id', 'name']]]);

        $this->doTestAttributes($resourceMetadataFactoryProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $requestStack);
    }

    public function testAttributes(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo'], 'attributes' => ['relatedDummy' => ['id', 'name']]]]));

        $this->doTestAttributes($resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestAttributes($resourceMetadataFactory, $serializerContextBuilder = null, ?RequestStack $requestStack = null): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', ['serializer_groups' => ['foo']])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', ['serializer_groups' => ['foo']])->willReturn($namePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn(true)->shouldBeCalled();
            }
        }

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name}')->shouldBeCalledTimes(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactory, 30, false, $requestStack, $serializerContextBuilder, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    /**
     * @group legacy
     */
    public function testLegacyNotInAttributes(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $request = Request::create('/api/dummies', 'GET', []);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest($request, true)->shouldBeCalled()->willReturn([AbstractNormalizer::GROUPS => ['foo'], AbstractNormalizer::ATTRIBUTES => ['relatedDummy']]);

        $this->doTestNotInAttributes($resourceMetadataFactoryProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $requestStack);
    }

    public function testNotInAttributes(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo'], 'attributes' => ['relatedDummy']]]));

        $this->doTestNotInAttributes($resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestNotInAttributes($resourceMetadataFactory, $serializerContextBuilder = null, ?RequestStack $requestStack = null): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactory, 30, false, $requestStack, $serializerContextBuilder, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    /**
     * @group legacy
     */
    public function testLegacyOnlyOneRelationNotInAttributes(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $request = Request::create('/api/dummies', 'GET', []);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest($request, true)->shouldBeCalled()->willReturn([AbstractNormalizer::GROUPS => ['foo'], AbstractNormalizer::ATTRIBUTES => ['relatedDummy' => ['id', 'name']]]);

        $this->doTestOnlyOneRelationNotInAttributes($resourceMetadataFactoryProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $requestStack);
    }

    public function testOnlyOneRelationNotInAttributes(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo'], 'attributes' => ['relatedDummy' => ['id', 'name']]]]));

        $this->doTestOnlyOneRelationNotInAttributes($resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestOnlyOneRelationNotInAttributes($resourceMetadataFactory, $serializerContextBuilder = null, ?RequestStack $requestStack = null): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', ['serializer_groups' => ['foo']])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', ['serializer_groups' => ['foo']])->willReturn($namePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummies' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ('id' !== $property) {
                $relatedClassMetadataProphecy->hasField($property)->willReturn(true)->shouldBeCalled();
            }
        }

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name}')->shouldBeCalledTimes(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactory, 30, false, $requestStack, $serializerContextBuilder, true);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionNoPartial()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo']]]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [];
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('relatedDummy2_a2')->shouldBeCalledTimes(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionWithANonReadableButFetchEagerProperty()
    {
        $this->doTestApplyToCollectionWithANonReadableButFetchEagerProperty(false);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyToCollectionWithANonReadableButFetchEagerProperty()
    {
        $this->doTestApplyToCollectionWithANonReadableButFetchEagerProperty(true);
    }

    private function doTestApplyToCollectionWithANonReadableButFetchEagerProperty(bool $legacy)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo']]]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withAttributes([$legacy ? 'fetchEager' : 'fetch_eager' => true]);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(false);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [];
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('relatedDummy_a1')->shouldBeCalledTimes(1);
        $queryBuilderProphecy->addSelect('relatedDummy2_a2')->shouldBeCalledTimes(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToCollectionWithAReadableButNotFetchEagerProperty()
    {
        $this->doTestApplyToCollectionWithAReadableButNotFetchEagerProperty(false);
    }

    /**
     * @group legacy
     */
    public function testLegacyApplyToCollectionWithAReadableButNotFetchEagerProperty()
    {
        $this->doTestApplyToCollectionWithAReadableButNotFetchEagerProperty(true);
    }

    private function doTestApplyToCollectionWithAReadableButNotFetchEagerProperty(bool $legacy)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata())->withAttributes(['normalization_context' => ['groups' => ['foo']]]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withAttributes([$legacy ? 'fetchEager' : 'fetch_eager' => false]);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $relationPropertyMetadata = $relationPropertyMetadata->withReadable(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', ['serializer_groups' => ['foo']])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => ClassMetadataInfo::FETCH_EAGER, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldNotBecalled();

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldNotBeCalled();
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldNotBeCalled();
        $queryBuilderProphecy->addSelect('relatedDummy_a1')->shouldNotBeCalled();
        $queryBuilderProphecy->addSelect('relatedDummy2_a2')->shouldNotBeCalled();

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }
}
