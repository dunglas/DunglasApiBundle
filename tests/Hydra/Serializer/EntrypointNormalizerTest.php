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

namespace ApiPlatform\Core\Tests\Hydra\Serializer;

use ApiPlatform\Core\Api\Entrypoint;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hydra\Serializer\EntrypointNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FooDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EntrypointNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportNormalization()
    {
        $collection = new ResourceNameCollection();
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization($entrypoint, EntrypointNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($entrypoint, 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), EntrypointNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizeWithResourceMetadata()
    {
        $collection = new ResourceNameCollection([FooDummy::class, Dummy::class]);
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, ['get']))->shouldBeCalled();
        $factoryProphecy->create(FooDummy::class)->willReturn(new ResourceMetadata('FooDummy', null, null, null, ['get']))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResourceClass(FooDummy::class)->willReturn('/api/foo_dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'Entrypoint'])->willReturn('/context/Entrypoint')->shouldBeCalled();

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => '/context/Entrypoint',
            '@id' => '/api',
            '@type' => 'Entrypoint',
            'dummy' => '/api/dummies',
            'fooDummy' => '/api/foo_dummies',
        ];
        $this->assertEquals($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }

    public function testNormalizeWithResourceCollection()
    {
        $collection = new ResourceNameCollection([FooDummy::class, Dummy::class]);
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceCollection([new Resource(uriTemplate: 'Dummy', shortName: 'dummy', description: null, types: [], operations: ['get' => new Get(collection: true)])]))->shouldBeCalled();
        $factoryProphecy->create(FooDummy::class)->willReturn(new ResourceCollection([new Resource(uriTemplate: 'FooDummy', shortName: 'fooDummy', description: null, types: [], operations: ['get' => new Get(collection: true)])]))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResourceClass(FooDummy::class)->willReturn('/api/foo_dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'Entrypoint'])->willReturn('/context/Entrypoint')->shouldBeCalled();

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => '/context/Entrypoint',
            '@id' => '/api',
            '@type' => 'Entrypoint',
            'dummy' => '/api/dummies',
            'fooDummy' => '/api/foo_dummies',
        ];
        $this->assertEquals($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }
}
