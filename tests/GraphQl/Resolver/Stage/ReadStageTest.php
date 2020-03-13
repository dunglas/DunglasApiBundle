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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStage;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ReadStageTest extends TestCase
{
    /** @var ReadStage */
    private $readStage;
    private $resourceMetadataFactoryProphecy;
    private $iriConverterProphecy;
    private $collectionDataProviderProphecy;
    private $subresourceDataProviderProphecy;
    private $serializerContextFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->collectionDataProviderProphecy = $this->prophesize(ContextAwareCollectionDataProviderInterface::class);
        $this->subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $this->serializerContextFactoryProphecy = $this->prophesize(SerializerContextFactoryInterface::class);

        $this->readStage = new ReadStage(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->iriConverterProphecy->reveal(),
            $this->collectionDataProviderProphecy->reveal(),
            $this->subresourceDataProviderProphecy->reveal(),
            $this->serializerContextFactoryProphecy->reveal(),
            '_'
        );
    }

    /**
     * @dataProvider contextProvider
     *
     * @param object|iterable|null $expectedResult
     */
    public function testApplyDisabled(array $context, $expectedResult): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['read' => false],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $result = ($this->readStage)($resourceClass, null, $operationName, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function contextProvider(): array
    {
        return [
            'item context' => [['is_collection' => false], null],
            'collection context' => [['is_collection' => true], []],
        ];
    }

    /**
     * @dataProvider itemProvider
     *
     * @param object|null $item
     * @param object|null $expectedResult
     */
    public function testApplyItem(?string $identifier, $item, bool $throwNotFound, $expectedResult): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $context = [
            'is_collection' => false,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => ['id' => $identifier],
            'info' => $info,
        ];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $normalizationContext = ['normalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, true, $context)->shouldBeCalled()->willReturn($normalizationContext);

        if ($throwNotFound) {
            $this->iriConverterProphecy->getItemFromIri($identifier, $normalizationContext)->willThrow(new ItemNotFoundException());
        } else {
            $this->iriConverterProphecy->getItemFromIri($identifier, $normalizationContext)->willReturn($item);
        }

        $result = ($this->readStage)($resourceClass, null, $operationName, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function itemProvider(): array
    {
        $item = new \stdClass();

        return [
            'no identifier' => [null, $item, false, null],
            'identifier' => ['identifier', $item, false, $item],
            'identifier not found' => ['identifier_not_found', $item, true, null],
        ];
    }

    /**
     * @dataProvider itemMutationOrSubscriptionProvider
     *
     * @param object|null $item
     * @param object|null $expectedResult
     */
    public function testApplyMutationOrSubscription(bool $isMutation, bool $isSubscription, string $resourceClass, ?string $identifier, $item, bool $throwNotFound, $expectedResult, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $operationName = 'create';
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $context = [
            'is_collection' => false,
            'is_mutation' => $isMutation,
            'is_subscription' => $isSubscription,
            'args' => ['input' => ['id' => $identifier]],
            'info' => $info,
        ];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata('shortName'));

        $normalizationContext = ['normalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, true, $context)->shouldBeCalled()->willReturn($normalizationContext);

        if ($throwNotFound) {
            $this->iriConverterProphecy->getItemFromIri($identifier, $normalizationContext)->willThrow(new ItemNotFoundException());
        } else {
            $this->iriConverterProphecy->getItemFromIri($identifier, $normalizationContext)->willReturn($item);
        }

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $result = ($this->readStage)($resourceClass, null, $operationName, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function itemMutationOrSubscriptionProvider(): array
    {
        $item = new \stdClass();

        return [
            'no identifier' => [true, false, 'myResource', null, $item, false, null],
            'identifier' => [true, false, 'stdClass', 'identifier', $item, false, $item],
            'identifier bad item' => [true, false, 'myResource', 'identifier', $item, false, $item, \UnexpectedValueException::class, 'Item "identifier" did not match expected type "shortName".'],
            'identifier not found' => [true, false, 'myResource', 'identifier_not_found', $item, true, null, NotFoundHttpException::class, 'Item "identifier_not_found" not found.'],
            'no identifier (subscription)' => [false, true, 'myResource', null, $item, false, null],
            'identifier (subscription)' => [false, true, 'stdClass', 'identifier', $item, false, $item],
        ];
    }

    /**
     * @dataProvider collectionProvider
     */
    public function testApplyCollection(array $args, ?string $rootClass, ?array $source, array $expectedFilters, iterable $expectedResult): void
    {
        $operationName = 'collection_query';
        $resourceClass = 'myResource';
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $fieldName = 'subresource';
        $info->fieldName = $fieldName;
        $context = [
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => $args,
            'info' => $info,
            'source' => $source,
        ];
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $normalizationContext = ['normalization' => true];
        $this->serializerContextFactoryProphecy->create($resourceClass, $operationName, true, $context)->shouldBeCalled()->willReturn($normalizationContext);

        $this->subresourceDataProviderProphecy->getSubresource($resourceClass, ['id' => 3], $normalizationContext + ['filters' => $expectedFilters, 'property' => $fieldName, 'identifiers' => [['id', $resourceClass]], 'collection' => true], $operationName)->willReturn(['subresource']);

        $this->collectionDataProviderProphecy->getCollection($resourceClass, $operationName, $normalizationContext + ['filters' => $expectedFilters])->willReturn($expectedResult);

        $result = ($this->readStage)($resourceClass, $rootClass, $operationName, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function collectionProvider(): array
    {
        return [
            'no root class' => [[], null, null, [], []],
            'nominal' => [
                ['filter_list' => 'filtered', 'filter_field_list' => ['filtered1', 'filtered2']],
                'myResource',
                null,
                ['filter_list' => 'filtered', 'filter_field_list' => ['filtered1', 'filtered2'], 'filter.list' => 'filtered', 'filter_field' => ['filtered1', 'filtered2'], 'filter.field' => ['filtered1', 'filtered2']],
                [],
            ],
            'with subresource' => [
                [],
                'myResource',
                ['subresource' => [], ItemNormalizer::ITEM_IDENTIFIERS_KEY => ['id' => 3]],
                [],
                ['subresource'],
            ],
        ];
    }
}
