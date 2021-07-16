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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\ResourceCollection\Factory\AttributeResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeDefaultOperations;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AttributeResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $attributeResourceCollectionMetadataFactory = new AttributeResourceCollectionMetadataFactory();

        $this->assertEquals(
            new ResourceCollection([
                new Resource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    operations: [
                        '_api_AttributeResource_get' => new Get(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeResource_put' => new Put(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeResource_delete' => new Delete(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder'),
                    ]
                ),
                new Resource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                    identifiers: ['dummyId' => [Dummy::class, 'id'], 'id' => [AttributeResource::class, 'id']],
                    operations: [
                        '_api_/dummy/{dummyId}/attribute_resources/{id}_get' => new Get(
                            class: AttributeResource::class,
                            uriTemplate: '/dummy/{dummyId}/attribute_resources/{id}',
                            shortName: 'AttributeResource',
                            identifiers: ['dummyId' => [Dummy::class, 'id'], 'id' => [AttributeResource::class, 'id']],
                        ),
                    ]
                ),
            ]),
            $attributeResourceCollectionMetadataFactory->create(AttributeResource::class)
        );

        $this->assertEquals(
            new ResourceCollection([
                new Resource(
                    uriTemplate: '/attribute_resources.{_format}',
                    shortName: 'AttributeResources',
                    class: AttributeResources::class,
                    operations: [
                        '_api_/attribute_resources.{_format}_get' => new Get(shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}'),
                        '_api_/attribute_resources.{_format}_post' => new Post(shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}'),
                    ]
                ),
            ]),
            $attributeResourceCollectionMetadataFactory->create(AttributeResources::class)
        );

        $this->assertEquals(
            new ResourceCollection([
                new Resource(
                    shortName: 'AttributeDefaultOperations',
                    class: AttributeDefaultOperations::class,
                    operations: [
                        '_api_AttributeDefaultOperations_get' => new Get(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_put' => new Put(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_delete' => new Delete(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_get_collection' => new GetCollection(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_patch' => new Patch(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_post' => new Post(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                    ]
                ),
            ]),
            $attributeResourceCollectionMetadataFactory->create(AttributeDefaultOperations::class)
        );
    }
}
