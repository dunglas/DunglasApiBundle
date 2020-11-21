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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DataCollector;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataCollector\RequestDataCollector;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataPersister\TraceableChainDataPersister;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainCollectionDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainItemDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainSubresourceDataProvider;
use ApiPlatform\Core\DataPersister\ChainDataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\ChainCollectionDataProvider;
use ApiPlatform\Core\DataProvider\ChainItemDataProvider;
use ApiPlatform\Core\DataProvider\ChainSubresourceDataProvider;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PackageVersions\Versions;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @author Julien DENIAU <julien.deniau@gmail.com>
 */
class RequestDataCollectorTest extends TestCase
{
    use ProphecyTrait;

    private $request;
    private $response;
    private $attributes;
    private $metadataFactory;
    private $filterLocator;

    protected function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->attributes = $this->prophesize(ParameterBag::class);
        $this->request = $this->prophesize(Request::class);
        $this->request
            ->getAcceptableContentTypes()
            ->shouldBeCalled()
            ->willReturn(['foo', 'bar']);

        $this->metadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->filterLocator = $this->prophesize(ContainerInterface::class);
    }

    public function testNoResourceClass()
    {
        $this->apiResourceClassWillReturn(null);

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertSame([], $dataCollector->getRequestAttributes());
        $this->assertSame([], $dataCollector->getFilters());
        $this->assertSame(['ignored_filters' => 0], $dataCollector->getCounters());
        $this->assertSame(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertNull($dataCollector->getResourceMetadata());

        $expected = ['context' => [], 'responses' => []];
        $this->assertSame($expected, $dataCollector->getCollectionDataProviders());
        $this->assertSame($expected, $dataCollector->getItemDataProviders());
        $this->assertSame($expected, $dataCollector->getSubresourceDataProviders());
        $this->assertSame(['responses' => []], $dataCollector->getDataPersisters());
    }

    public function testNotCallingCollect()
    {
        $this->request
            ->getAcceptableContentTypes()
            ->shouldNotBeCalled();
        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal()
        );

        $this->assertSame([], $dataCollector->getRequestAttributes());
        $this->assertSame([], $dataCollector->getAcceptableContentTypes());
        $this->assertSame([], $dataCollector->getFilters());
        $this->assertSame([], $dataCollector->getCounters());
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertNull($dataCollector->getResourceMetadata());

        $expected = ['context' => [], 'responses' => []];
        $this->assertSame($expected, $dataCollector->getCollectionDataProviders());
        $this->assertSame($expected, $dataCollector->getItemDataProviders());
        $this->assertSame($expected, $dataCollector->getSubresourceDataProviders());
        $this->assertSame(['responses' => []], $dataCollector->getDataPersisters());
    }

    public function testWithResource()
    {
        $this->apiResourceClassWillReturn(DummyEntity::class, ['_api_item_operation_name' => 'get', '_api_receive' => true]);
        $this->request->attributes = $this->attributes->reveal();

        $this->filterLocator->has('foo')->willReturn(false)->shouldBeCalled();
        $this->filterLocator->has('a_filter')->willReturn(true)->shouldBeCalled();
        $this->filterLocator->get('a_filter')->willReturn(new \stdClass())->shouldBeCalled();

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal(),
            new ChainCollectionDataProvider([]),
            new ChainItemDataProvider([]),
            new ChainSubresourceDataProvider([]),
            new ChainDataPersister([])
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertSame([
            'resource_class' => DummyEntity::class,
            'item_operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
            'cache_tags_invalidation' => true,
        ], $dataCollector->getRequestAttributes());
        $this->assertSame(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertSame(DummyEntity::class, $dataCollector->getResourceClass());
        $this->assertSame(['foo' => null, 'a_filter' => \stdClass::class], $dataCollector->getFilters());
        $this->assertSame(['ignored_filters' => 1], $dataCollector->getCounters());
        $this->assertInstanceOf(Data::class, $dataCollector->getResourceMetadata());
        $this->assertSame(ResourceMetadata::class, $dataCollector->getResourceMetadata()->getType());

        $expected = ['context' => [], 'responses' => []];
        $this->assertSame($expected, $dataCollector->getCollectionDataProviders());
        $this->assertSame($expected, $dataCollector->getItemDataProviders());
        $this->assertSame($expected, $dataCollector->getSubresourceDataProviders());
        $this->assertSame(['responses' => []], $dataCollector->getDataPersisters());
    }

    public function testWithResourceWithTraceables()
    {
        $this->apiResourceClassWillReturn(DummyEntity::class);

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal(),
            $this->getUsedCollectionDataProvider(),
            $this->getUsedItemDataProvider(),
            $this->getUsedSubresourceDataProvider(),
            $this->getUsedPersister()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $dataProvider = $dataCollector->getCollectionDataProviders();
        foreach ($dataProvider['responses'] as $class => $response) {
            $this->assertStringContainsString('@anonymous', $class);

            $this->assertTrue($response);
        }
        $context = $dataProvider['context'];
        $this->assertInstanceOf(Data::class, $context);
        $this->assertSame(['collection_context'], $context->getValue(true));

        $dataProvider = $dataCollector->getItemDataProviders();
        foreach ($dataProvider['responses'] as $class => $response) {
            $this->assertStringContainsString('@anonymous', $class);
            $this->assertTrue($response);
        }
        $context = $dataProvider['context'];
        $this->assertInstanceOf(Data::class, $context);
        $this->assertSame(['item_context'], $context->getValue(true));

        $dataProvider = $dataCollector->getSubresourceDataProviders();
        foreach ($dataProvider['responses'] as $class => $response) {
            $this->assertStringContainsString('@anonymous', $class);
            $this->assertTrue($response);
        }
        $context = $dataProvider['context'];
        $this->assertInstanceOf(Data::class, $context);
        $this->assertSame(['subresource_context'], $context->getValue(true));

        $dataPersister = $dataCollector->getDataPersisters();
        foreach ($dataPersister['responses'] as $class => $response) {
            $this->assertStringContainsString('@anonymous', $class);
            $this->assertTrue($response);
        }
    }

    public function testVersionCollection()
    {
        $this->apiResourceClassWillReturn(DummyEntity::class);

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal(),
            $this->getUsedCollectionDataProvider(),
            $this->getUsedItemDataProvider(),
            $this->getUsedSubresourceDataProvider(),
            $this->getUsedPersister()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertSame(null !== $dataCollector->getVersion(), class_exists(Versions::class));
    }

    private function apiResourceClassWillReturn($data, $context = [])
    {
        $this->attributes->get('_api_resource_class')->shouldBeCalled()->willReturn($data);
        $this->attributes->all()->shouldBeCalled()->willReturn([
            '_api_resource_class' => $data,
        ] + $context);
        $this->request->attributes = $this->attributes->reveal();

        if (!$data) {
            $this->metadataFactory
                ->create()
                ->shouldNotBeCalled();
        } else {
            $this->metadataFactory
                ->create($data)
                ->shouldBeCalled()
                ->willReturn(
                    new ResourceMetadata(null, null, null, [], [], ['filters' => ['foo', 'a_filter']])
                );
        }
    }

    private function getUsedCollectionDataProvider(): TraceableChainCollectionDataProvider
    {
        $collectionDataProvider = new TraceableChainCollectionDataProvider(new ChainCollectionDataProvider([
            new class() implements CollectionDataProviderInterface {
                public function getCollection(string $resourceClass, string $operationName = null)
                {
                    return [];
                }
            },
        ]));
        $collectionDataProvider->getCollection('', '', ['collection_context']);

        return $collectionDataProvider;
    }

    private function getUsedItemDataProvider(): TraceableChainItemDataProvider
    {
        $itemDataProvider = new TraceableChainItemDataProvider(new ChainItemDataProvider([
            new class() implements ItemDataProviderInterface {
                public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
                {
                    return null;
                }
            },
        ]));
        $itemDataProvider->getItem('', '', null, ['item_context']);

        return $itemDataProvider;
    }

    private function getUsedSubresourceDataProvider(): TraceableChainSubresourceDataProvider
    {
        $subresourceDataProvider = new TraceableChainSubresourceDataProvider(new ChainSubresourceDataProvider([
            new class() implements SubresourceDataProviderInterface {
                public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
                {
                    return null;
                }
            },
        ]));
        $subresourceDataProvider->getSubresource('', [], ['subresource_context']);

        return $subresourceDataProvider;
    }

    private function getUsedPersister(): TraceableChainDataPersister
    {
        $dataPersister = new TraceableChainDataPersister(new ChainDataPersister([
            new class() implements DataPersisterInterface {
                public function supports($data): bool
                {
                    return true;
                }

                public function persist($data)
                {
                }

                public function remove($data)
                {
                }
            },
        ]));
        $dataPersister->persist('');

        return $dataPersister;
    }
}
