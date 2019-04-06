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

namespace ApiPlatform\Core\Tests\Operation\Factory;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactory;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Tests\Fixtures\DummyValidatedEntity;
use ApiPlatform\Core\Tests\Fixtures\Metadata\Property\MappingPropertyMetadataFactory;
use ApiPlatform\Core\Tests\Fixtures\Metadata\Property\MappingPropertyNameCollectionMetadataFactory;
use ApiPlatform\Core\Tests\Fixtures\Metadata\Resource\MappingResourceMetadataFactory;
use ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class SubresourceOperationFactoryTest extends TestCase
{
    public function testCreate()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => new ResourceMetadata('dummyEntity'),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['foo', 'subresource', 'subcollection']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = DummyEntity::class,
                $property = 'foo'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                $resourceClass = DummyEntity::class,
                $property = 'subcollection'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_item_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_item_delete_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_item_put_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_post_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_post_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_post_subresource',
                'method' => 'POST',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_item_get_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_item_delete_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_item_put_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/subcollections.{_format}',
                'operation_name' => 'subcollections_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_post_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_post_subresource',
                'path' => '/dummy_entities/{id}/subcollections.{_format}',
                'operation_name' => 'subcollections_post_subresource',
                'method' => 'POST',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_item_get_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_item_get_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subcollections_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_item_delete_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subcollections_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_item_put_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_item_put_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subcollections_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_item_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_item_delete_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_item_put_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subcollections/{subcollection}/another_subresource/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateByOverriding()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity'))
                ->withSubresourceOperations([
                    'subcollections_get_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars',
                    ],
                    'subcollections_post_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars',
                    ],
                    'subcollections_item_get_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}',
                    ],
                    'subcollections_item_delete_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}',
                    ],
                    'subcollections_item_put_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}',
                    ],
                    'subcollections_another_subresource_get_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                    ],
                    'subcollections_another_subresource_post_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                    ],
                    'subcollections_another_subresource_item_get_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                    ],
                    'subcollections_another_subresource_item_delete_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                    ],
                    'subcollections_another_subresource_item_put_subresource' => [
                        'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                    ],
                ]),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['foo', 'subresource', 'subcollection']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = DummyEntity::class,
                $property = 'foo'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                $resourceClass = DummyEntity::class,
                $property = 'subcollection'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_item_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_item_delete_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_item_put_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource.{_format}',
                'operation_name' => 'subresource_another_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_post_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_post_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_post_subresource',
                'method' => 'POST',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_item_get_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_item_delete_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_another_subresource_subcollections_item_put_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, false, 'subresource'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subresource_another_subresource_subcollections_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource/another_subresource/subcollections/{subcollection}.{_format}',
                'operation_name' => 'subresource_another_subresource_subcollections_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_get_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_get_subresource',
                'path' => '/dummy_entities/{id}/foobars',
                'operation_name' => 'subcollections_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_post_subresource' => [
                'property' => 'subcollection',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_post_subresource',
                'path' => '/dummy_entities/{id}/foobars',
                'operation_name' => 'subcollections_post_subresource',
                'method' => 'POST',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_item_get_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_item_get_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}',
                'operation_name' => 'subcollections_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_item_delete_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_item_delete_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}',
                'operation_name' => 'subcollections_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_item_put_subresource' => [
                'property' => 'subcollection',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_item_put_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}',
                'operation_name' => 'subcollections_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_item_get_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                'operation_name' => 'subcollections_another_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_item_delete_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                'operation_name' => 'subcollections_another_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_item_put_subresource' => [
                'property' => 'anotherSubresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar.{_format}',
                'operation_name' => 'subcollections_another_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subcollections_another_subresource_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subcollection', RelatedDummyEntity::class, true, 'subcollection'],
                    ['anotherSubresource', DummyEntity::class, false, 'anotherSubresource'],
                ],
                'route_name' => 'api_dummy_entities_subcollections_another_subresource_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/foobars/{subcollection}/another_foobar/subresource.{_format}',
                'operation_name' => 'subcollections_another_subresource_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateWithMaxDepth()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    /**
     * Test for issue: https://github.com/api-platform/core/issues/1711.
     */
    public function testCreateWithMaxDepthMultipleSubresources()
    {
        /**
         * DummyEntity -subresource-> RelatedDummyEntity -anotherSubresource-> DummyEntity
         * DummyEntity -secondSubresource-> dummyValidatedEntity -moreSubresource-> RelatedDummyEntity.
         */
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
            DummyValidatedEntity::class => new ResourceMetadata('dummyValidatedEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource', 'secondSubresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['moreSubresource']),
                $resourceClass = DummyValidatedEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyValidatedEntity::class, $collection = false, $maxDepth = 2)),
                $resourceClass = DummyEntity::class,
                $property = 'secondSubresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false)),
                $resourceClass = DummyValidatedEntity::class,
                $property = 'moreSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_item_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_item_delete_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_item_put_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_more_subresource_item_get_subresource' => [
                'property' => 'moreSubresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['secondSubresource', DummyValidatedEntity::class, false, 'secondSubresource'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_more_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresource/more_subresource.{_format}',
                'operation_name' => 'second_subresource_more_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_more_subresource_item_delete_subresource' => [
                'property' => 'moreSubresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['secondSubresource', DummyValidatedEntity::class, false, 'secondSubresource'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_more_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/second_subresource/more_subresource.{_format}',
                'operation_name' => 'second_subresource_more_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_more_subresource_item_put_subresource' => [
                'property' => 'moreSubresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['secondSubresource', DummyValidatedEntity::class, false, 'secondSubresource'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_more_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/second_subresource/more_subresource.{_format}',
                'operation_name' => 'second_subresource_more_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    /**
     * Test for issue: https://github.com/api-platform/core/issues/2103.
     */
    public function testCreateWithMaxDepthMultipleSubresourcesSameMaxDepth()
    {
        /**
         * DummyEntity -subresource (maxDepth=1)-> RelatedDummyEntity -anotherSubresource-> DummyEntity
         * DummyEntity -secondSubresource (maxDepth=1)-> dummyValidatedEntity -moreSubresource-> RelatedDummyEntity.
         */
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
            DummyValidatedEntity::class => new ResourceMetadata('dummyValidatedEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource', 'secondSubresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['moreSubresource']),
                $resourceClass = DummyValidatedEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyValidatedEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'secondSubresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false)),
                $resourceClass = DummyValidatedEntity::class,
                $property = 'moreSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_item_get_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_item_delete_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_second_subresource_item_put_subresource' => [
                'property' => 'secondSubresource',
                'collection' => false,
                'resource_class' => DummyValidatedEntity::class,
                'shortNames' => ['dummyEntity', 'dummyValidatedEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_second_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/second_subresource.{_format}',
                'operation_name' => 'second_subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    public function testCreateSelfReferencingSubresources()
    {
        /**
         * DummyEntity -subresource-> DummyEntity -subresource-> DummyEntity ...
         */
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }

    /**
     * @group legacy
     * @expectedDeprecation Declaring identifier property "id" (in class ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity) with @ApiSubresource as a workaround (https://github.com/api-platform/core/pull/1875) to enable subresource GET item operation is deprecated and will cause an error in Api-Platform 3.0. The operation(s) is now offered by default. Please remove the @ApiSubresource declaration in the "id" property.
     */
    public function testCreateWithEnd()
    {
        /**
         * TODO: next major: remove the workaround.
         *
         * https://github.com/api-platform/core/pull/1875
         *
         * DummyEntity                        RelatedDummyEntity
         * -subresource:RelatedDummyEntity    - id:int(@ApiSubresource)
         *
         * /dummies/{id}/subresources/{subresource}/id/{id} becomes /dummies/{id}/subresources/{subresource}
         */
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['id']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false))->withIdentifier(true),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'id'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $result = $subresourceOperationFactory->create(DummyEntity::class);
        $this->assertEquals([
            'api_dummy_entities_subresources_get_subresource' => [
                'property' => 'subresource',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresources_get_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresources_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresources_post_subresource' => [
                'property' => 'subresource',
                'collection' => true,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresources_post_subresource',
                'path' => '/dummy_entities/{id}/subresources.{_format}',
                'operation_name' => 'subresources_post_subresource',
                'method' => 'POST',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresources_item_delete_subresource' => [
                'property' => 'id',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, true, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresources_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresources/{subresource}.{_format}',
                'operation_name' => 'subresources_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresources_item_put_subresource' => [
                'property' => 'id',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, true, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresources_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresources/{subresource}.{_format}',
                'operation_name' => 'subresources_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresources_item_get_subresource' => [
                'property' => 'id',
                'collection' => false,
                'resource_class' => DummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                    ['subresource', RelatedDummyEntity::class, true, 'subresource'],
                ],
                'route_name' => 'api_dummy_entities_subresources_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresources/{subresource}.{_format}',
                'operation_name' => 'subresources_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $result);
    }

    /**
     * @dataProvider provideCreateAll
     */
    public function testCreateAll(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        string $rootResourceClass,
        array $expectedSubresourceOperations,
        array $expectedDeprecations
    ) {
        $subresourceOperationFactory = new SubresourceOperationFactory(
            $resourceMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            new UnderscorePathSegmentNameGenerator()
        );

        $actualDeprecations = [];
        set_error_handler(function ($type, $msg) use (&$actualDeprecations) { $actualDeprecations[] = $msg; });
        $e = error_reporting(E_USER_DEPRECATED);

        $actualSubresourceOperations = $subresourceOperationFactory->create($rootResourceClass);

        error_reporting($e);
        restore_error_handler();

        $sort = function (&$array) use (&$sort) {
            foreach ($array as &$value) {
                if (\is_array($value)) {
                    $sort($value);
                }
            }

            return ksort($array);
        };

        $sort($expectedSubresourceOperations);
        $sort($actualSubresourceOperations);
        $this->assertEquals(json_encode($expectedSubresourceOperations, JSON_PRETTY_PRINT), json_encode($actualSubresourceOperations, JSON_PRETTY_PRINT));

        $this->assertSame($expectedDeprecations, $actualDeprecations);
    }

    public function provideCreateAll()
    {
        return [
            'Subresource (collection = false) should result in item operations (GET, PUT, DELETE)' => [
                $resourceMetadataFactory = new MappingResourceMetadataFactory([
                    DummyEntity::class => (new ResourceMetadata('dummyEntity')),
                ]),
                $propertyNameCollectionFactory = MappingPropertyNameCollectionMetadataFactory::from()
                    ->withMetadata(
                        new PropertyNameCollection(['anotherSubresource']),
                        $resourceClass = DummyEntity::class
                    ),
                $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
                    ->withMetadata(
                        (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                        $resourceClass = DummyEntity::class,
                        $property = 'anotherSubresource'
                    ),
                $rootResourceClass = DummyEntity::class,
                $expectedSubresourceOperations = [
                    'api_dummy_entities_another_subresource_item_delete_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresource_item_delete_subresource',
                        'path' => '/dummy_entities/{id}/another_subresource.{_format}',
                        'operation_name' => 'another_subresource_item_delete_subresource',
                        'method' => 'DELETE',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresource_item_put_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresource_item_put_subresource',
                        'path' => '/dummy_entities/{id}/another_subresource.{_format}',
                        'operation_name' => 'another_subresource_item_put_subresource',
                        'method' => 'PUT',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresource_item_get_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresource_item_get_subresource',
                        'path' => '/dummy_entities/{id}/another_subresource.{_format}',
                        'operation_name' => 'another_subresource_item_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                ],
                $expectedDeprecations = [],
            ],
            'The later identifier of the same identifier name in the subresource chain should be renamed to a concatenated name' => [
                $resourceMetadataFactory = new MappingResourceMetadataFactory([
                    DummyEntity::class => (new ResourceMetadata('dummyEntity')),
                    RelatedDummyEntity::class => (new ResourceMetadata('relatedDummyEntity')),
                    DummyValidatedEntity::class => (new ResourceMetadata('dummyValidatedEntity')),
                ]),
                $propertyNameCollectionFactory = MappingPropertyNameCollectionMetadataFactory::from()
                    ->withMetadata(
                        new PropertyNameCollection(['anotherSubresource']),
                        $resourceClass = DummyEntity::class
                    )
                    ->withMetadata(
                        new PropertyNameCollection(['anotherSubresource']),
                        $resourceClass = RelatedDummyEntity::class
                    )
                    ->withMetadata(
                        new PropertyNameCollection(['anotherSubresource']),
                        $resourceClass = DummyValidatedEntity::class
                    ),
                $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
                    ->withMetadata(
                        (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                        $resourceClass = DummyEntity::class,
                        $property = 'anotherSubresource'
                    )
                    ->withMetadata(
                        (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyValidatedEntity::class, $collection = true)),
                        $resourceClass = RelatedDummyEntity::class,
                        $property = 'anotherSubresource'
                    )
                    ->withMetadata(
                        (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = true)),
                        $resourceClass = DummyValidatedEntity::class,
                        $property = 'anotherSubresource'
                    ),
                $rootResourceClass = DummyEntity::class,
                $expectedSubresourceOperations = [
                    'api_dummy_entities_another_subresources_get_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => true,
                        'resource_class' => RelatedDummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_get_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources.{_format}',
                        'operation_name' => 'another_subresources_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_post_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => true,
                        'resource_class' => RelatedDummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_post_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources.{_format}',
                        'operation_name' => 'another_subresources_post_subresource',
                        'method' => 'POST',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_item_delete_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => RelatedDummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_item_delete_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_item_delete_subresource',
                        'method' => 'DELETE',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_item_put_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => RelatedDummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_item_put_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_item_put_subresource',
                        'method' => 'PUT',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_item_get_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => RelatedDummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_item_get_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_item_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_get_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => true,
                        'resource_class' => DummyValidatedEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_get_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_post_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => true,
                        'resource_class' => DummyValidatedEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_post_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_post_subresource',
                        'method' => 'POST',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_item_delete_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyValidatedEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_item_delete_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_item_delete_subresource',
                        'method' => 'DELETE',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_item_put_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyValidatedEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_item_put_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_item_put_subresource',
                        'method' => 'PUT',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_item_get_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyValidatedEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_item_get_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_item_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_another_subresources_get_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => true,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_another_subresources_get_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}/another_subresources.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_another_subresources_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_another_subresources_post_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => true,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_another_subresources_post_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}/another_subresources.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_another_subresources_post_subresource',
                        'method' => 'POST',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_another_subresources_item_delete_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                            ['anotherSubresource', DummyEntity::class, true, 'anotherSubresourceAnotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_another_subresources_item_delete_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresourceAnotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_another_subresources_item_delete_subresource',
                        'method' => 'DELETE',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_another_subresources_item_put_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                            ['anotherSubresource', DummyEntity::class, true, 'anotherSubresourceAnotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_another_subresources_item_put_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresourceAnotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_another_subresources_item_put_subresource',
                        'method' => 'PUT',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_another_subresources_another_subresources_another_subresources_item_get_subresource' => [
                        'property' => 'anotherSubresource',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity', 'dummyValidatedEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['anotherSubresource', RelatedDummyEntity::class, true, 'anotherSubresource'],
                            ['anotherSubresource', DummyValidatedEntity::class, true, 'anotherSubresourceAnotherSubresource'],
                            ['anotherSubresource', DummyEntity::class, true, 'anotherSubresourceAnotherSubresourceAnotherSubresource'],
                        ],
                        'route_name' => 'api_dummy_entities_another_subresources_another_subresources_another_subresources_item_get_subresource',
                        'path' => '/dummy_entities/{id}/another_subresources/{anotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresource}/another_subresources/{anotherSubresourceAnotherSubresourceAnotherSubresource}.{_format}',
                        'operation_name' => 'another_subresources_another_subresources_another_subresources_item_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                ],
                $expectedDeprecations = [],
            ],
            'Subresource (collection = true) should result in collection operations (GET, POST) and item operations (GET, PUT, DELETE)' => [
                $resourceMetadataFactory = new MappingResourceMetadataFactory([
                    DummyEntity::class => (new ResourceMetadata('dummyEntity')),
                ]),
                $propertyNameCollectionFactory = MappingPropertyNameCollectionMetadataFactory::from()
                    ->withMetadata(
                        new PropertyNameCollection(['subcollection']),
                        $resourceClass = DummyEntity::class
                    ),
                $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
                    ->withMetadata(
                        (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = true)),
                        $resourceClass = DummyEntity::class,
                        $property = 'subcollection'
                    ),
                $rootResourceClass = DummyEntity::class,
                $expectedSubresourceOperations = [
                    'api_dummy_entities_subcollections_get_subresource' => [
                        'property' => 'subcollection',
                        'collection' => true,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_subcollections_get_subresource',
                        'path' => '/dummy_entities/{id}/subcollections.{_format}',
                        'operation_name' => 'subcollections_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subcollections_post_subresource' => [
                        'property' => 'subcollection',
                        'collection' => true,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_subcollections_post_subresource',
                        'path' => '/dummy_entities/{id}/subcollections.{_format}',
                        'operation_name' => 'subcollections_post_subresource',
                        'method' => 'POST',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subcollections_item_delete_subresource' => [
                        'property' => 'subcollection',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['subcollection', DummyEntity::class, true, 'subcollection'],
                        ],
                        'route_name' => 'api_dummy_entities_subcollections_item_delete_subresource',
                        'path' => '/dummy_entities/{id}/subcollections/{subcollection}.{_format}',
                        'operation_name' => 'subcollections_item_delete_subresource',
                        'method' => 'DELETE',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subcollections_item_put_subresource' => [
                        'property' => 'subcollection',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['subcollection', DummyEntity::class, true, 'subcollection'],
                        ],
                        'route_name' => 'api_dummy_entities_subcollections_item_put_subresource',
                        'path' => '/dummy_entities/{id}/subcollections/{subcollection}.{_format}',
                        'operation_name' => 'subcollections_item_put_subresource',
                        'method' => 'PUT',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subcollections_item_get_subresource' => [
                        'property' => 'subcollection',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['subcollection', DummyEntity::class, true, 'subcollection'],
                        ],
                        'route_name' => 'api_dummy_entities_subcollections_item_get_subresource',
                        'path' => '/dummy_entities/{id}/subcollections/{subcollection}.{_format}',
                        'operation_name' => 'subcollections_item_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                ],
                $expectedDeprecations = [],
            ],
            // TODO: next major: remove the workaround test case (they are deprecated)
            'BC: Subresource (collection = true, identifier = true) should result in collection operations (GET, POST) and item operations (GET, PUT, DELETE) and a deprecation notice' => [
                $resourceMetadataFactory = new MappingResourceMetadataFactory([
                    DummyEntity::class => (new ResourceMetadata('dummyEntity')),
                    RelatedDummyEntity::class => (new ResourceMetadata('relatedDummyEntity')),
                ]),
                $propertyNameCollectionFactory = MappingPropertyNameCollectionMetadataFactory::from()
                    ->withMetadata(
                        new PropertyNameCollection(['subresource']),
                        $resourceClass = DummyEntity::class
                    )
                    ->withMetadata(
                        new PropertyNameCollection(['id']),
                        $resourceClass = RelatedDummyEntity::class
                    ),
                $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
                    ->withMetadata(
                        (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = true)),
                        $resourceClass = DummyEntity::class,
                        $property = 'subresource'
                    )
                    ->withMetadata(
                        (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false))->withIdentifier(true),
                        $resourceClass = RelatedDummyEntity::class,
                        $property = 'id'
                    ),
                $rootResourceClass = DummyEntity::class,
                $expectedSubresourceOperations = [
                    'api_dummy_entities_subresources_get_subresource' => [
                        'property' => 'subresource',
                        'collection' => true,
                        'resource_class' => RelatedDummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_subresources_get_subresource',
                        'path' => '/dummy_entities/{id}/subresources.{_format}',
                        'operation_name' => 'subresources_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subresources_post_subresource' => [
                        'property' => 'subresource',
                        'collection' => true,
                        'resource_class' => RelatedDummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                        ],
                        'route_name' => 'api_dummy_entities_subresources_post_subresource',
                        'path' => '/dummy_entities/{id}/subresources.{_format}',
                        'operation_name' => 'subresources_post_subresource',
                        'method' => 'POST',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subresources_item_delete_subresource' => [
                        'property' => 'id',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['subresource', RelatedDummyEntity::class, true, 'subresource'],
                        ],
                        'route_name' => 'api_dummy_entities_subresources_item_delete_subresource',
                        'path' => '/dummy_entities/{id}/subresources/{subresource}.{_format}',
                        'operation_name' => 'subresources_item_delete_subresource',
                        'method' => 'DELETE',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subresources_item_put_subresource' => [
                        'property' => 'id',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['subresource', RelatedDummyEntity::class, true, 'subresource'],
                        ],
                        'route_name' => 'api_dummy_entities_subresources_item_put_subresource',
                        'path' => '/dummy_entities/{id}/subresources/{subresource}.{_format}',
                        'operation_name' => 'subresources_item_put_subresource',
                        'method' => 'PUT',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                    'api_dummy_entities_subresources_item_get_subresource' => [
                        'property' => 'id',
                        'collection' => false,
                        'resource_class' => DummyEntity::class,
                        'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                        'identifiers' => [
                            ['id', DummyEntity::class, true, 'id'],
                            ['subresource', RelatedDummyEntity::class, true, 'subresource'],
                        ],
                        'route_name' => 'api_dummy_entities_subresources_item_get_subresource',
                        'path' => '/dummy_entities/{id}/subresources/{subresource}.{_format}',
                        'operation_name' => 'subresources_item_get_subresource',
                        'method' => 'GET',
                    ] + SubresourceOperationFactory::ROUTE_OPTIONS,
                ],
                $expectedDeprecations = [
                    'Declaring identifier property "id" (in class ApiPlatform\Core\Tests\Fixtures\RelatedDummyEntity) with @ApiSubresource as a workaround (https://github.com/api-platform/core/pull/1875) to enable subresource GET item operation is deprecated and will cause an error in Api-Platform 3.0. The operation(s) is now offered by default. Please remove the @ApiSubresource declaration in the "id" property.',
                ],
            ],
        ];
    }

    public function testCreateWithEndButNoCollection()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => (new ResourceMetadata('dummyEntity')),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['id']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false))->withIdentifier(true),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'id'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $result = $subresourceOperationFactory->create(DummyEntity::class);
        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $result);
    }

    public function testCreateWithRootResourcePrefix()
    {
        $resourceMetadataFactory = new MappingResourceMetadataFactory([
            DummyEntity::class => new ResourceMetadata('dummyEntity', null, null, null, null, ['route_prefix' => 'root_resource_prefix']),
            RelatedDummyEntity::class => new ResourceMetadata('relatedDummyEntity'),
        ]);

        $propertyNameCollectionMetadataFactory = MappingPropertyNameCollectionMetadataFactory::from()
            ->withMetadata(
                new PropertyNameCollection(['subresource']),
                $resourceClass = DummyEntity::class
            )
            ->withMetadata(
                new PropertyNameCollection(['bar', 'anotherSubresource']),
                $resourceClass = RelatedDummyEntity::class
            );

        $propertyMetadataFactory = MappingPropertyMetadataFactory::from()
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = RelatedDummyEntity::class, $collection = false, $maxDepth = 1)),
                $resourceClass = DummyEntity::class,
                $property = 'subresource'
            )
            ->withMetadata(
                new PropertyMetadata(),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'bar'
            )
            ->withMetadata(
                (new PropertyMetadata())->withSubresource(new SubresourceMetadata($resourceClass = DummyEntity::class, $collection = false)),
                $resourceClass = RelatedDummyEntity::class,
                $property = 'anotherSubresource'
            );

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionMetadataFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $this->assertEquals([
            'api_dummy_entities_subresource_item_get_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_get_subresource',
                'path' => '/root_resource_prefix/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_get_subresource',
                'method' => 'GET',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_delete_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_delete_subresource',
                'path' => '/root_resource_prefix/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_delete_subresource',
                'method' => 'DELETE',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
            'api_dummy_entities_subresource_item_put_subresource' => [
                'property' => 'subresource',
                'collection' => false,
                'resource_class' => RelatedDummyEntity::class,
                'shortNames' => ['dummyEntity', 'relatedDummyEntity'],
                'identifiers' => [
                    ['id', DummyEntity::class, true, 'id'],
                ],
                'route_name' => 'api_dummy_entities_subresource_item_put_subresource',
                'path' => '/root_resource_prefix/dummy_entities/{id}/subresource.{_format}',
                'operation_name' => 'subresource_item_put_subresource',
                'method' => 'PUT',
            ] + SubresourceOperationFactory::ROUTE_OPTIONS,
        ], $subresourceOperationFactory->create(DummyEntity::class));
    }
}
