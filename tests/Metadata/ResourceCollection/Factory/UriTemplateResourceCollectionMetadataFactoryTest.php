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

use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\UriTemplateResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class UriTemplateResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $pathSegmentNameGeneratorProphecy = $this->prophesize(PathSegmentNameGeneratorInterface::class);
        $pathSegmentNameGeneratorProphecy->getSegmentName('AttributeResource')->willReturn('attribute_resources');
        $pathSegmentNameGeneratorProphecy->getSegmentName('AttributeDefaultOperations')->willReturn('attribute_default_operations');
        $resourceCollectionMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceCollectionMetadataFactory->create(AttributeResource::class)->willReturn(
            new ResourceCollection([
                new Resource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    identifiers: ['id' => [AttributeResource::class, 'id']],
                    operations: [
                        '_api_AttributeResource_get' => new Get(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_put' => new Put(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_delete' => new Delete(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_AttributeResource_get_collection' => new GetCollection(shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: []),
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
        );
        $uriTemplateResourceCollectionMetadataFactory = new UriTemplateResourceCollectionMetadataFactory($pathSegmentNameGeneratorProphecy->reveal(), $resourceCollectionMetadataFactory->reveal());

        $this->assertEquals(
            new ResourceCollection([
                new Resource(
                    identifiers: ['id' => [AttributeResource::class, 'id']],
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    operations: [
                        '_api_/attribute_resources/{id}.{_format}_get' => new Get(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_/attribute_resources/{id}.{_format}_put' => new Put(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_/attribute_resources/{id}.{_format}_delete' => new Delete(uriTemplate: '/attribute_resources/{id}.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: ['id' => [AttributeResource::class, 'id']]),
                        '_api_/attribute_resources.{_format}_get' => new GetCollection(uriTemplate: '/attribute_resources.{_format}', shortName: 'AttributeResource', class: AttributeResource::class, controller: 'api_platform.action.placeholder', identifiers: []),
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
            $uriTemplateResourceCollectionMetadataFactory->create(AttributeResource::class)
        );
    }
}
