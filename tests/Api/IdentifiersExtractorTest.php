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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Generator\Uuid;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Model\ResourceInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Model\ResourceInterfaceImplementation;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class IdentifiersExtractorTest extends TestCase
{
    use ProphecyTrait;

    public function testGetIdentifiersFromResourceClass()
    {
        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $this->getMetadataFactoryProphecies(Dummy::class, ['id']);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame(['id'], $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
    }

    public function testGetCompositeIdentifiersFromResourceClass()
    {
        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'name']);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame(['id', 'name'], $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
    }

    public function itemProvider()
    {
        $dummy = new Dummy();
        $dummy->setId(1);
        yield [$dummy, ['id' => 1]];

        $uuid = new Uuid();
        $dummy = new Dummy();
        $dummy->setId($uuid);
        yield [$dummy, ['id' => $uuid]];
    }

    /**
     * @dataProvider itemProvider
     */
    public function testGetIdentifiersFromItem($item, $expected)
    {
        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $this->getMetadataFactoryProphecies(Dummy::class, ['id']);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
    }

    public function itemProviderComposite()
    {
        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setName('foo');
        yield [$dummy, ['id' => 1, 'name' => 'foo']];

        $dummy = new Dummy();
        $dummy->setId($uuid = new Uuid());
        $dummy->setName('foo');
        yield [$dummy, ['id' => $uuid, 'name' => 'foo']];
    }

    /**
     * @dataProvider itemProviderComposite
     */
    public function testGetCompositeIdentifiersFromItem($item, $expected)
    {
        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'name']);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
    }

    public function itemProviderRelated()
    {
        $related = new RelatedDummy();
        $related->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);
        yield [$dummy, ['id' => 1, 'relatedDummy' => 2]];

        $uuid2 = new Uuid();
        $related = new RelatedDummy();
        $related->setId($uuid2);

        $uuid = new Uuid();
        $dummy = new Dummy();
        $dummy->setId($uuid);
        $dummy->setRelatedDummy($related);
        yield [$dummy, ['id' => $uuid, 'relatedDummy' => $uuid2]];
    }

    /**
     * @dataProvider itemProviderRelated
     */
    public function testGetRelatedIdentifiersFromItem($item, $expected)
    {
        $prophecies = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'relatedDummy']);
        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $this->getMetadataFactoryProphecies(RelatedDummy::class, ['id'], $prophecies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(RelatedDummy::class))->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
    }

    public function testThrowNoIdentifierFromItem()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No identifier found in "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\RelatedDummy" through relation "relatedDummy" of "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy" used as identifier');

        $related = new RelatedDummy();
        $related->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);

        $prophecies = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'relatedDummy']);
        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $this->getMetadataFactoryProphecies(RelatedDummy::class, [], $prophecies);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(RelatedDummy::class))->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $identifiersExtractor->getIdentifiersFromItem($dummy);
    }

    public function testGetsIdentifiersFromCorrectResourceClass(): void
    {
        $item = new ResourceInterfaceImplementation();
        $item->setFoo('woot');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(ResourceInterface::class)->willReturn(new PropertyNameCollection(['foo', 'fooz']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(ResourceInterface::class, 'foo')->willReturn((new PropertyMetadata())->withIdentifier(true));
        $propertyMetadataFactoryProphecy->create(ResourceInterface::class, 'fooz')->willReturn(new PropertyMetadata());

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(ResourceInterface::class);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $identifiersExtractor->getIdentifiersFromItem($item);

        $this->assertSame(['foo' => 'woot'], $identifiersExtractor->getIdentifiersFromItem($item));
    }

    public function testNoIdentifiers(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No identifier defined in "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy". You should add #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]" on the property identifying the resource.');
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn(new PropertyNameCollection(['foo']));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo')->willReturn(new PropertyMetadata());
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class);
    }

    /**
     * @group legacy
     * @expectedDeprecation Not injecting ApiPlatform\Core\Api\ResourceClassResolverInterface in the IdentifiersExtractor might introduce cache issues with object identifiers.
     */
    public function testLegacyGetIdentifiersFromItem()
    {
        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $this->getMetadataFactoryProphecies(Dummy::class, ['id']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $dummy = new Dummy();
        $dummy->setId(1);

        $this->assertSame(['id' => 1], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }

    private function getMetadataFactoryProphecies($class, $identifiers, array $prophecies = null)
    {
        //adds a random property that is not an identifier
        $properties = array_merge(['foo'], $identifiers);

        if (!$prophecies) {
            $prophecies = [$this->prophesize(PropertyNameCollectionFactoryInterface::class), $this->prophesize(PropertyMetadataFactoryInterface::class)];
        }

        [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy] = $prophecies;

        $propertyNameCollectionFactoryProphecy->create($class)->willReturn(new PropertyNameCollection($properties));

        foreach ($properties as $prop) {
            $metadata = new PropertyMetadata();
            $propertyMetadataFactoryProphecy->create($class, $prop)->willReturn($metadata->withIdentifier(\in_array($prop, $identifiers, true)));
        }

        return [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy];
    }

    public function testDefaultIdentifierId(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->willReturn(new PropertyMetadata());
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame(['id'], $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
    }

    /**
     * @requires PHP 8.0
     */
    public function testGetIdentifiersFromItemWithContext()
    {
        $a = new AttributeResource(1, 'test');
        $a->dummy = new Dummy();
        $a->dummy->setId(1);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($a)->willReturn(AttributeResource::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(AttributeResource::class)->willReturn(new PropertyNameCollection(['identifier', 'dummy']));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'identifier')->willReturn((new PropertyMetadata())->withIdentifier(true));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'dummy')->willReturn((new PropertyMetadata())->withType(new Type('object', true, Dummy::class)));

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());
        $this->assertSame(['identifier' => 1, 'dummyId' => 1], $identifiersExtractor->getIdentifiersFromItem($a, ['identifiers' => ['identifier' => [AttributeResource::class, 'identifier'], 'dummyId' => [Dummy::class, 'id']]]));
    }
}
