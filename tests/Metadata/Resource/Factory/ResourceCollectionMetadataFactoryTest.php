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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ResourceCollectionMetadataFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     * @group legacy
     */
    public function testCreateAttribute()
    {
        $this->expectDeprecation('Using a ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface for a #[Resource] is deprecated since 2.7 and will not be possible in 3.0. Use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface instead.');
        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(AttributeResource::class)->willThrow(ResourceClassNotFoundException::class);
        $resourceCollectionMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $operations = ['get' => new Get('/resources', normalizationContext: ['groups' => ['a']]), 'get_item' => new Get('/resources/{id}', identifiers: ['id' => ['id', AttributeResource::class]])];
        $resourceCollectionMetadataFactory->create(AttributeResource::class)->willReturn(new ResourceCollection([new Resource(operations: $operations)]));
        $factory = new ResourceCollectionMetadataFactory($decorated->reveal(), $resourceCollectionMetadataFactory->reveal());
        $metadata = $factory->create(AttributeResource::class);

        $this->assertCount(1, $metadata->getItemOperations());
        $this->assertEquals(['id' => ['id', AttributeResource::class]], $metadata->getItemOperationAttribute('get_item', 'identifiers'));
        $this->assertCount(1, $metadata->getCollectionOperations());
        $this->assertEquals(['groups' => ['a']], $metadata->getCollectionOperationAttribute('get', 'normalization_context'));
    }
}
