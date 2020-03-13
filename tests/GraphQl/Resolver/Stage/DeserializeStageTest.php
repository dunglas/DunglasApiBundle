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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Stage;

use ApiPlatform\Core\GraphQl\Resolver\Stage\DeserializeStage;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DeserializeStageTest extends TestCase
{
    /** @var DeserializeStage */
    private $deserializeStage;
    private $resourceMetadataFactoryProphecy;
    private $denormalizerProphecy;
    private $serializerContextFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $this->serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $this->deserializeStage = new DeserializeStage(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->denormalizerProphecy->reveal(),
            $this->serializerContextFactoryProphecy->reveal()
        );
    }

    /**
     * @dataProvider objectToPopulateProvider
     *
     * @param object|null $objectToPopulate
     */
    public function testApplyDisabled($objectToPopulate): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['deserialize' => false],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $result = ($this->deserializeStage)($objectToPopulate, $resourceClass, $operationName, []);

        $this->assertSame($objectToPopulate, $result);
    }

    /**
     * @dataProvider objectToPopulateProvider
     *
     * @param object|null $objectToPopulate
     */
    public function testApply($objectToPopulate, array $denormalizationContext): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $context = ['args' => ['input' => 'myInput']];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, false, $context)->shouldBeCalled()->willReturn($denormalizationContext);

        $denormalizedData = new \stdClass();
        $this->denormalizerProphecy->denormalize($context['args']['input'], $resourceClass, ItemNormalizer::FORMAT, $denormalizationContext)->shouldBeCalled()->willReturn($denormalizedData);

        $result = ($this->deserializeStage)($objectToPopulate, $resourceClass, $operationName, $context);

        $this->assertSame($denormalizedData, $result);
    }

    public function objectToPopulateProvider(): array
    {
        return [
            'null' => [null, ['denormalization' => true]],
            'object' => [$object = new \stdClass(), ['denormalization' => true, ItemNormalizer::OBJECT_TO_POPULATE => $object]],
        ];
    }
}
