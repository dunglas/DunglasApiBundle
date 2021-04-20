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

namespace ApiPlatform\Core\Tests\JsonLd\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @group legacy
     */
    public function testDontSupportDenormalization()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceClassResolverProphecy->getResourceClass(['dummy'], 'Dummy')->willReturn(Dummy::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name' => 'name']));

        $normalizer = new ItemNormalizer($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $contextBuilderProphecy->reveal());

        $this->assertFalse($normalizer->supportsDenormalization('foo', ItemNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    /**
     * @group legacy
     */
    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false)->shouldBeCalled();

        $normalizer = new ItemNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $contextBuilderProphecy->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy, 'jsonld'));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, 'jsonld'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $defaultOptions = ['enable_getter_setter_extraction' => true];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy'));
        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, $defaultOptions)->willReturn($propertyNameCollection);

        $propertyMetadata = new PropertyMetadata(null, null, true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', $defaultOptions)->willReturn($propertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1988');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilderProphecy->getResourceContextUri(Dummy::class)->willReturn('/contexts/Dummy');

        $normalizer = new ItemNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $contextBuilderProphecy->reveal(),
            null,
            null,
            null,
            [],
            []
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            '@context' => '/contexts/Dummy',
            '@id' => '/dummies/1988',
            '@type' => 'Dummy',
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy));
    }
}
