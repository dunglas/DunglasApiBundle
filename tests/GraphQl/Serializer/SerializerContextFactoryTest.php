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

namespace ApiPlatform\Core\Tests\GraphQl\Serializer;

use ApiPlatform\Core\GraphQl\Serializer\SerializerContextFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SerializerContextFactoryTest extends TestCase
{
    /** @var SerializerContextFactory */
    private $serializerContextFactory;
    private $resourceMetadataFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $this->serializerContextFactory = new SerializerContextFactory(
            $this->resourceMetadataFactoryProphecy->reveal(),
            new CustomConverter()
        );
    }

    /**
     * @dataProvider createNormalizationContextProvider
     */
    public function testCreateNormalizationContext(?string $resourceClass, string $operationName, array $fields, bool $isMutation, bool $isSubscription, bool $noInfo, array $expectedContext, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $resolverContext = [
            'is_mutation' => $isMutation,
            'is_subscription' => $isSubscription,
        ];

        if ($noInfo) {
            $resolverContext['fields'] = $fields;
        } else {
            $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
            $resolveInfoProphecy->getFieldSelection(PHP_INT_MAX)->willReturn($fields);
            $resolverContext['info'] = $resolveInfoProphecy->reveal();
        }

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(
            (new ResourceMetadata('shortName'))
                ->withGraphql([
                    $operationName => [
                        'input' => ['class' => 'inputClass'],
                        'output' => ['class' => 'outputClass'],
                        'normalization_context' => ['groups' => ['normalization_group']],
                    ],
                ])
        );

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $context = $this->serializerContextFactory->create($resourceClass, $operationName, true, $resolverContext);

        $this->assertSame($expectedContext, $context);
    }

    public function createNormalizationContextProvider(): array
    {
        return [
            'nominal' => [
                $resourceClass = 'myResource',
                $operationName = 'item_query',
                ['_id' => 3, 'field' => 'foo'],
                false,
                false,
                false,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'id' => 3,
                        'field' => 'foo',
                    ],
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
            ],
            'nominal collection' => [
                $resourceClass = 'myResource',
                $operationName = 'collection_query',
                ['edges' => ['node' => ['nodeField' => 'baz']]],
                false,
                false,
                false,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'nodeField' => 'baz',
                    ],
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
            ],
            'no resource class' => [
                $resourceClass = null,
                $operationName = 'item_query',
                ['related' => ['_id' => 9]],
                false,
                false,
                false,
                [
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'related' => ['id' => 9],
                    ],
                ],
            ],
            'mutation' => [
                $resourceClass = 'myResource',
                $operationName = 'create',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                true,
                false,
                false,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'id' => 7,
                        'related' => ['field' => 'bar'],
                    ],
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
            ],
            'mutation without resource class' => [
                $resourceClass = null,
                $operationName = 'create',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                true,
                false,
                false,
                [],
                \LogicException::class,
                'ResourceMetadata should always exist for a mutation or a subscription.',
            ],
            'subscription (using fields in context)' => [
                $resourceClass = 'myResource',
                $operationName = 'update',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                false,
                true,
                true,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'id' => 7,
                        'related' => ['field' => 'bar'],
                    ],
                    'no_resolver_data' => true,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createDenormalizationContextProvider
     */
    public function testCreateDenormalizationContext(?string $resourceClass, string $operationName, array $expectedContext): void
    {
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(
            (new ResourceMetadata())
                ->withGraphql([
                    $operationName => [
                        'input' => ['class' => 'inputClass'],
                        'output' => ['class' => 'outputClass'],
                        'denormalization_context' => ['groups' => ['denormalization_group']],
                    ],
                ])
        );

        $context = $this->serializerContextFactory->create($resourceClass, $operationName, false, []);

        $this->assertSame($expectedContext, $context);
    }

    public function createDenormalizationContextProvider(): array
    {
        return [
            'nominal' => [
                $resourceClass = 'myResource',
                $operationName = 'item_query',
                [
                    'groups' => ['denormalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
            ],
            'no resource class' => [
                $resourceClass = null,
                $operationName = 'item_query',
                [
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                ],
            ],
        ];
    }
}
