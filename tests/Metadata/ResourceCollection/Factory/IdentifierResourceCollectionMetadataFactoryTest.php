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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\AttributeResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\IdentifierResourceCollectionMetadataFactory;
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
class IdentifierResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $propertyNameCollectionProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionProphecy->create(AttributeResource::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyNameCollectionProphecy->create(AttributeDefaultOperations::class)->willReturn(new PropertyNameCollection(['id']));
        $propertyNameCollectionProphecy->create(AttributeResources::class)->willReturn(new PropertyNameCollection([]));
        $propertyMetadataProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataProphecy->create(AttributeResource::class, 'id')->willReturn(new PropertyMetadata(identifier: true));
        $propertyMetadataProphecy->create(AttributeDefaultOperations::class, 'id')->willReturn(new PropertyMetadata(identifier: true));
        $decorated = new AttributeResourceCollectionMetadataFactory();
        $identifierResourceCollectionMetadataFactory = new IdentifierResourceCollectionMetadataFactory($decorated, $propertyNameCollectionProphecy->reveal(), $propertyMetadataProphecy->reveal());

        $this->assertEquals(
            new ResourceCollection([
                new Resource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    identifiers: ['id' => [AttributeResource::class, 'id']],
                    operations: [
                        '_api_AttributeResource_get' => new Get(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_put' => new Put(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_delete' => new Delete(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
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
            $identifierResourceCollectionMetadataFactory->create(AttributeResource::class)
        );

        $this->assertEquals(
            new ResourceCollection([
                new Resource(
                    uriTemplate: '/attribute_resources.{_format}',
                    identifiers: [],
                    shortName: 'AttributeResources',
                    class: AttributeResources::class,
                    operations: [
                        '_api_/attribute_resources.{_format}_get' => new Get(shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}', identifiers: []),
                        '_api_/attribute_resources.{_format}_post' => new Post(shortName: 'AttributeResources', class: AttributeResources::class, controller: 'api_platform.action.placeholder', uriTemplate: '/attribute_resources.{_format}', identifiers: []),
                    ]
                ),
            ]),
            $identifierResourceCollectionMetadataFactory->create(AttributeResources::class)
        );

        $this->assertEquals(
            new ResourceCollection([
                new Resource(
                    shortName: 'AttributeDefaultOperations',
                    class: AttributeDefaultOperations::class,
                    identifiers: ['id' => [AttributeDefaultOperations::class, 'id']],
                    operations: [
                        '_api_AttributeDefaultOperations_get' => new Get(identifiers: ['id' => [AttributeDefaultOperations::class, 'id']], shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_put' => new Put(identifiers: ['id' => [AttributeDefaultOperations::class, 'id']], shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_delete' => new Delete(identifiers: ['id' => [AttributeDefaultOperations::class, 'id']], shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_get_collection' => new GetCollection(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_patch' => new Patch(identifiers: ['id' => [AttributeDefaultOperations::class, 'id']], shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                        '_api_AttributeDefaultOperations_post' => new Post(shortName: 'AttributeDefaultOperations', class: AttributeDefaultOperations::class, controller: 'api_platform.action.placeholder'),
                    ]
                ),
            ]),
            $identifierResourceCollectionMetadataFactory->create(AttributeDefaultOperations::class)
        );
    }
}
