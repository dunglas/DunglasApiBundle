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

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\Filter\FilterInterface as LegacySerializerFilterInterface;
use ApiPlatform\Core\Serializer\Filter\SerializerContextFilterInterface as SerializerFilterInterface;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerFilterContextFactory;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class SerializerFilterContextFactoryTest extends TestCase
{
    public function testCreateWithCollectionOperation(): void
    {
        $context = [
            'resource_class' => DummyGroup::class,
            'collection_operation_name' => 'get',
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['filters' => ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']]]
        );

        $decoratedProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $decoratedProphecy->create(DummyGroup::class, 'get', true, $context)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->applyToSerializerContext(DummyGroup::class, 'get', true, $context, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextFactory($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->create(DummyGroup::class, 'get', true, $context);
    }

    public function testCreateWithItemOperation(): void
    {
        $context = [
            'resource_class' => DummyGroup::class,
            'item_operation_name' => 'put',
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            ['put' => ['filters' => ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']]],
            null
        );

        $decoratedProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $decoratedProphecy->create(DummyGroup::class, 'put', true, $context)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->applyToSerializerContext(DummyGroup::class, 'put', true, $context, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextFactory($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->create(DummyGroup::class, 'put', true, $context);
    }

    public function testCreateWithoutFilters(): void
    {
        $context = [
            'resource_class' => DummyGroup::class,
            'collection_operation_name' => 'get',
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => []]
        );

        $decoratedProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $decoratedProphecy->create(DummyGroup::class, 'get', false, $context)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $serializerContextBuilderFilter = new SerializerFilterContextFactory($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->create(DummyGroup::class, 'get', false, $context);
    }

    public function testCreateExceptionLegacyFilter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('%The filter "Double\\\\FilterInterface\\\\\\w+" implements the "ApiPlatform\\\\Core\\\\Serializer\\\\Filter\\\\FilterInterface" interface but "ApiPlatform\\\\Core\\\\Serializer\\\\SerializerFilterContextFactory" is only compatible with filters implementing the "ApiPlatform\\\\Core\\\\Serializer\\\\Filter\\\\SerializerContextFilterInterface" interface\\.%');

        $context = [
            'resource_class' => DummyGroup::class,
            'item_operation_name' => 'put',
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            ['put' => ['filters' => ['dummy_group.group']]],
            null
        );

        $decoratedProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
        $decoratedProphecy->create(DummyGroup::class, 'put', true, $context)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(LegacySerializerFilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextFactory($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->create(DummyGroup::class, 'put', true, $context);
    }
}
