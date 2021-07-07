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

use ApiPlatform\Core\Metadata\ResourceCollection\Factory\CachedResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class CachedResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateWithItemHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItem->get()->willReturn(new ResourceCollection(new Resource(shortName: 'Dummy', class: Dummy::class)))->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();

        $decoratedResourceMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);

        $cachedResourceMetadataFactory = new CachedResourceCollectionMetadataFactory($cacheItemPool->reveal(), $decoratedResourceMetadataFactory->reveal());
        $resultedResourceMetadata = $cachedResourceMetadataFactory->create(Dummy::class);

        $this->assertEquals(new ResourceCollection(new Resource(shortName: 'Dummy', class: Dummy::class)), $resultedResourceMetadata);
    }

    public function testCreateWithItemNotHit()
    {
        $resources = new ResourceCollection(new Resource(shortName: 'Dummy', class: Dummy::class));

        $decoratedResourceMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $decoratedResourceMetadataFactory->create(Dummy::class)->willReturn($resources)->shouldBeCalled();

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItem->set($resources)->willReturn($cacheItem->reveal())->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();
        $cacheItemPool->save($cacheItem->reveal())->willReturn(true)->shouldBeCalled();

        $cachedResourceMetadataFactory = new CachedResourceCollectionMetadataFactory($cacheItemPool->reveal(), $decoratedResourceMetadataFactory->reveal());
        $resultedResourceMetadata = $cachedResourceMetadataFactory->create(Dummy::class);

        $this->assertEquals($resources, $resultedResourceMetadata);
        $this->assertEquals($resources, $cachedResourceMetadataFactory->create(Dummy::class), 'Trigger the local cache');
    }

    public function testCreateWithGetCacheItemThrowsCacheException()
    {
        $decoratedResourceMetadataFactory = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $decoratedResourceMetadataFactory->create(Dummy::class)->willReturn(new ResourceCollection(new Resource(shortName: 'Dummy', class: Dummy::class)))->shouldBeCalled();

        $cacheException = new class() extends \Exception implements CacheException {};

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willThrow($cacheException)->shouldBeCalled();

        $cachedResourceMetadataFactory = new CachedResourceCollectionMetadataFactory($cacheItemPool->reveal(), $decoratedResourceMetadataFactory->reveal());
        $resultedResourceMetadata = $cachedResourceMetadataFactory->create(Dummy::class);

        $expectedResult = new ResourceCollection(new Resource(shortName: 'Dummy', class: Dummy::class));
        $this->assertEquals($expectedResult, $resultedResourceMetadata);
        $this->assertEquals($expectedResult, $cachedResourceMetadataFactory->create(Dummy::class), 'Trigger the local cache');
    }

    private function generateCacheKey(string $resourceClass = Dummy::class)
    {
        return CachedResourceCollectionMetadataFactory::CACHE_KEY_PREFIX.md5($resourceClass);
    }
}
