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

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\WriteStage;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class WriteStageTest extends TestCase
{
    /** @var WriteStage */
    private $writeStage;
    private $resourceMetadataFactoryProphecy;
    private $dataPersisterProphecy;
    private $serializerContextFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->dataPersisterProphecy = $this->prophesize(ContextAwareDataPersisterInterface::class);
        $this->serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $this->writeStage = new WriteStage(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->dataPersisterProphecy->reveal(),
            $this->serializerContextFactoryProphecy->reveal()
        );
    }

    public function testNoData(): void
    {
        $resourceClass = 'myResource';
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $result = ($this->writeStage)(null, $resourceClass, 'item_query', []);

        $this->assertNull($result);
    }

    public function testApplyDisabled(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['write' => false],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $data = new \stdClass();
        $result = ($this->writeStage)($data, $resourceClass, $operationName, []);

        $this->assertSame($data, $result);
    }

    public function testApplyDelete(): void
    {
        $operationName = 'delete';
        $resourceClass = 'myResource';
        $context = [];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $denormalizationContext = ['denormalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, false, $context)->willReturn($denormalizationContext);

        $data = new \stdClass();
        $this->dataPersisterProphecy->remove($data, $denormalizationContext)->shouldBeCalled();
        $this->dataPersisterProphecy->persist(Argument::cetera())->shouldNotBeCalled();

        $result = ($this->writeStage)($data, $resourceClass, $operationName, $context);

        $this->assertNull($result);
    }

    public function testApply(): void
    {
        $operationName = 'create';
        $resourceClass = 'myResource';
        $context = [];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $denormalizationContext = ['denormalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, false, $context)->willReturn($denormalizationContext);

        $data = new \stdClass();
        $persistedData = new \stdClass();
        $this->dataPersisterProphecy->remove(Argument::cetera())->shouldNotBeCalled();
        $this->dataPersisterProphecy->persist($data, $denormalizationContext)->shouldBeCalled()->willReturn($persistedData);

        $result = ($this->writeStage)($data, $resourceClass, $operationName, $context);

        $this->assertSame($persistedData, $result);
    }

    /**
     * @group legacy
     * @expectedDeprecation Not returning an object from ApiPlatform\Core\DataPersister\DataPersisterInterface::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3.
     */
    public function testLegacyApply(): void
    {
        $operationName = 'create';
        $resourceClass = 'myResource';
        $context = [];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $denormalizationContext = ['denormalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, false, $context)->willReturn($denormalizationContext);

        $data = new \stdClass();
        $this->dataPersisterProphecy->remove(Argument::cetera())->shouldNotBeCalled();
        $this->dataPersisterProphecy->persist($data, $denormalizationContext)->shouldBeCalled();

        $result = ($this->writeStage)($data, $resourceClass, $operationName, $context);

        $this->assertNull($result);
    }
}
