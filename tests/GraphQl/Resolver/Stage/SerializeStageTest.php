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

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStage;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SerializeStageTest extends TestCase
{
    private $resourceMetadataFactoryProphecy;
    private $normalizerProphecy;
    private $serializerContextFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $this->serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);
    }

    /**
     * @dataProvider applyDisabledProvider
     */
    public function testApplyDisabled(array $context, bool $paginationEnabled, ?array $expectedResult): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['serialize' => false],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $result = ($this->createSerializeStage($paginationEnabled))(null, $resourceClass, $operationName, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function applyDisabledProvider(): array
    {
        return [
            'item' => [['is_collection' => false, 'is_mutation' => false, 'is_subscription' => false], false, null],
            'collection with pagination' => [['is_collection' => true, 'is_mutation' => false, 'is_subscription' => false], true, ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]]],
            'collection without pagination' => [['is_collection' => true, 'is_mutation' => false, 'is_subscription' => false], false, []],
            'mutation' => [['is_collection' => false, 'is_mutation' => true, 'is_subscription' => false], false, ['clientMutationId' => null]],
            'subscription' => [['is_collection' => false, 'is_mutation' => false, 'is_subscription' => true], false, ['clientSubscriptionId' => null]],
        ];
    }

    /**
     * @dataProvider applyProvider
     *
     * @param object|iterable|null $itemOrCollection
     */
    public function testApply($itemOrCollection, string $operationName, array $context, bool $paginationEnabled, ?array $expectedResult): void
    {
        $resourceClass = 'myResource';
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata('shortName'));

        $normalizationContext = ['normalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, true, $context)->shouldBeCalled()->willReturn($normalizationContext);

        $this->normalizerProphecy->normalize(Argument::type('stdClass'), ItemNormalizer::FORMAT, $normalizationContext)->willReturn(['normalized_item']);

        $result = ($this->createSerializeStage($paginationEnabled))($itemOrCollection, $resourceClass, $operationName, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function applyProvider(): array
    {
        $defaultContext = [
            'args' => [],
            'info' => $this->prophesize(ResolveInfo::class)->reveal(),
        ];

        return [
            'item' => [new \stdClass(), 'item_query', $defaultContext + ['is_collection' => false, 'is_mutation' => false, 'is_subscription' => false], false, ['normalized_item']],
            'collection without pagination' => [[new \stdClass(), new \stdClass()], 'collection_query', $defaultContext + ['is_collection' => true, 'is_mutation' => false, 'is_subscription' => false], false, [['normalized_item'], ['normalized_item']]],
            'mutation' => [new \stdClass(), 'create', array_merge($defaultContext, ['args' => ['input' => ['clientMutationId' => 'clientMutationId']], 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false]), false, ['shortName' => ['normalized_item'], 'clientMutationId' => 'clientMutationId']],
            'delete mutation' => [new \stdClass(), 'delete', array_merge($defaultContext, ['args' => ['input' => ['id' => '/iri/4']], 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false]), false, ['shortName' => ['id' => '/iri/4'], 'clientMutationId' => null]],
            'subscription' => [new \stdClass(), 'update', array_merge($defaultContext, ['args' => ['input' => ['clientSubscriptionId' => 'clientSubscriptionId']], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]), false, ['shortName' => ['normalized_item'], 'clientSubscriptionId' => 'clientSubscriptionId']],
        ];
    }

    /**
     * @dataProvider applyCollectionWithPaginationProvider
     */
    public function testApplyCollectionWithPagination(iterable $collection, array $args, ?array $expectedResult, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $operationName = 'collection_query';
        $resourceClass = 'myResource';
        $context = [
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => $args,
            'info' => $this->prophesize(ResolveInfo::class)->reveal(),
        ];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata('shortName'));

        $normalizationContext = ['normalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, true, $context)->shouldBeCalled()->willReturn($normalizationContext);

        $this->normalizerProphecy->normalize(Argument::type('stdClass'), ItemNormalizer::FORMAT, $normalizationContext)->willReturn(['normalized_item']);

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $result = ($this->createSerializeStage(true))($collection, $resourceClass, $operationName, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function applyCollectionWithPaginationProvider(): array
    {
        return [
            'not paginator' => [[], [], null, \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\Core\DataProvider\PaginatorInterface'],
            'empty paginator' => [new ArrayPaginator([], 0, 0), [], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]]],
            'paginator' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 0, 2), [], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MA=='], ['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => false]]],
            'paginator with after cursor' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 2), ['after' => 'MA=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]]],
            'paginator with bad after cursor' => [new ArrayPaginator([], 0, 0), ['after' => '-'], null, \UnexpectedValueException::class, 'Cursor - is invalid'],
            'paginator with empty after cursor' => [new ArrayPaginator([], 0, 0), ['after' => ''], null, \UnexpectedValueException::class, 'Empty cursor is invalid'],
            'paginator with before cursor' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 1), ['before' => 'Mg=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => true]]],
            'paginator with bad before cursor' => [new ArrayPaginator([], 0, 0), ['before' => '-'], null, \UnexpectedValueException::class, 'Cursor - is invalid'],
            'paginator with empty before cursor' => [new ArrayPaginator([], 0, 0), ['before' => ''], null, \UnexpectedValueException::class, 'Empty cursor is invalid'],
            'paginator with last' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 2), ['last' => 2], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]]],
        ];
    }

    public function testApplyBadNormalizedData(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $context = ['is_collection' => false, 'is_mutation' => false, 'is_subscription' => false, 'args' => [], 'info' => $this->prophesize(ResolveInfo::class)->reveal()];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $normalizationContext = ['normalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, true, $context)->shouldBeCalled()->willReturn($normalizationContext);

        $this->normalizerProphecy->normalize(Argument::type('stdClass'), ItemNormalizer::FORMAT, $normalizationContext)->willReturn(new \stdClass());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected serialized data to be a nullable array.');

        ($this->createSerializeStage(false))(new \stdClass(), $resourceClass, $operationName, $context);
    }

    private function createSerializeStage(bool $paginationEnabled): SerializeStage
    {
        $pagination = new Pagination($this->resourceMetadataFactoryProphecy->reveal(), [], ['enabled' => $paginationEnabled]);

        return new SerializeStage(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->normalizerProphecy->reveal(),
            $this->serializerContextFactoryProphecy->reveal(),
            $pagination
        );
    }
}
