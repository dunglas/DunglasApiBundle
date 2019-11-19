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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Metadata\Property;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class DoctrineOrmPropertyMetadataFactoryTest extends TestCase
{
    public function testCreateNoManager()
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn(null);

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertEquals($doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsIdentifier()
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata = $propertyMetadata->withIdentifier(true);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldNotBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldNotBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertEquals($doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsWritable()
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata = $propertyMetadata->withWritable(false);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->getAssociationMappings()->shouldBeCalled()->willReturn([]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), false);
    }

    public function testCreateClassMetadataInfo()
    {
        $propertyMetadata = new PropertyMetadata();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->isIdentifierNatural()->shouldBeCalled()->willReturn(true);
        $classMetadata->getAssociationMappings()->shouldBeCalled()->willReturn([]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), true);
    }

    public function testCreateClassMetadata()
    {
        $propertyMetadata = new PropertyMetadata();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), false);
    }

    public function testCreateIsNullable()
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT);

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata = $propertyMetadata->withType($type);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'nullable_relation', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->getAssociationMappings()->shouldBeCalled()->willReturn([
            [
                'fieldName' => 'nullable_relation',
                'joinColumns' => [
                    ['nullable'=>true]
                ]
            ]
        ]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'nullable_relation');

        $this->assertEquals($doctrinePropertyMetadata->getType()->isNullable(), true);
    }

    public function testCreateIsNullableFalse()
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT);

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata = $propertyMetadata->withType($type);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'nullable_relation', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->getAssociationMappings()->shouldBeCalled()->willReturn([
            [
                'fieldName' => 'nullable_relation',
                'joinColumns' => [
                    ['nullable'=>true],
                    ['nillable'=>false]
                ]
            ]
        ]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'nullable_relation');

        $this->assertEquals($doctrinePropertyMetadata->getType()->isNullable(), false);
    }
}
