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

use ApiPlatform\Core\Operation\Factory\CachedSubresourceOperationFactory;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class CachedSubresourceOperationFactoryTest extends TestCase
{
    public function testCreateWithItemHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalledTimes(1);
        $cacheItem->get()->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalledTimes(1);
        $decoratedSubresourceOperationFactory = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $decoratedSubresourceOperationFactory->create()->shouldNotBeCalled();
        $cachedSubresourceOperationFactory = new CachedSubresourceOperationFactory($cacheItemPool->reveal(),
            $decoratedSubresourceOperationFactory->reveal());
        $expectedResult = ['foo' => 'bar'];
        $this->assertEquals($expectedResult,
            $cachedSubresourceOperationFactory->create(Dummy::class));
        $this->assertEquals($expectedResult,
            $cachedSubresourceOperationFactory->create(Dummy::class),
            'Trigger the local cache');
    }

    public function testCreateWithItemNotHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(false)->shouldBeCalledTimes(1);
        $cacheItem->set(['foo' => 'bar'])->willReturn($cacheItem->reveal())->shouldBeCalledTimes(1);
        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalledTimes(1);
        $cacheItemPool->save($cacheItem->reveal())->willReturn(true)->shouldBeCalledTimes(1);
        $decoratedSubresourceOperationFactory = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $decoratedSubresourceOperationFactory->create(Dummy::class)->shouldBeCalledTimes(1)->willReturn(['foo' => 'bar']);
        $cachedSubresourceOperationFactory = new CachedSubresourceOperationFactory($cacheItemPool->reveal(),
            $decoratedSubresourceOperationFactory->reveal());
        $expectedResult = ['foo' => 'bar'];
        $this->assertEquals($expectedResult,
            $cachedSubresourceOperationFactory->create(Dummy::class));
        $this->assertEquals($expectedResult,
            $cachedSubresourceOperationFactory->create(Dummy::class),
            'Trigger the local cache');
    }

    public function testCreateWithGetCacheItemThrowsCacheException()
    {
        $cacheException = $this->prophesize(\Exception::class);
        $cacheException->willImplement(CacheException::class);
        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willThrow($cacheException->reveal())->shouldBeCalledTimes(1);
        $decoratedSubresourceOperationFactory = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $decoratedSubresourceOperationFactory->create(Dummy::class)->shouldBeCalledTimes(1)->willReturn(['foo' => 'bar']);
        $cachedSubresourceOperationFactory = new CachedSubresourceOperationFactory($cacheItemPool->reveal(),
            $decoratedSubresourceOperationFactory->reveal());
        $expectedResult = ['foo' => 'bar'];
        $this->assertEquals($expectedResult,
            $cachedSubresourceOperationFactory->create(Dummy::class));
        $this->assertEquals($expectedResult,
            $cachedSubresourceOperationFactory->create(Dummy::class),
            'Trigger the local cache');
    }

    private function generateCacheKey(string $resourceClass = Dummy::class)
    {
        return CachedSubresourceOperationFactory::CACHE_KEY_PREFIX.md5($resourceClass);
    }
}
