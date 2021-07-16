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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\CachedIdentifiersExtractor;
use ApiPlatform\Core\Api\ContextAwareIdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Generator\Uuid;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class CachedIdentifiersExtractorTest extends TestCase
{
    use ProphecyTrait;

    public function itemProvider()
    {
        $dummy = new Dummy();
        $dummy->setId($id = 1);
        yield [$dummy, ['id' => $id]];

        $dummy = new Dummy();
        $dummy->setId($id = new Uuid());
        yield [$dummy, ['id' => $id]];
    }

    /**
     * @dataProvider itemProvider
     */
    public function testFirstPass($item, $expected)
    {
        $cacheItemKey = 'iri_identifiers'.md5(Dummy::class);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $cacheItemProphecy->set(['id'])->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem($cacheItemKey)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldBeCalled();

        $decoratedProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoratedProphecy->getIdentifiersFromItem($item, Argument::any())->willReturn($expected);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');

        $expected = ['id'];

        $decoratedProphecy->getIdentifiersFromResourceClass(Dummy::class)->willReturn($expected);

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class), 'Trigger the local cache');
    }

    /**
     * @dataProvider itemProvider
     */
    public function testSecondPass($item, $expected)
    {
        $cacheItemKey = 'iri_identifiers'.md5(Dummy::class);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn(['id']);

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem($cacheItemKey)->willReturn($cacheItemProphecy);

        $decoratedProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoratedProphecy->getIdentifiersFromItem($item)->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');

        $expected = ['id'];

        $decoratedProphecy->getIdentifiersFromResourceClass(Dummy::class)->willReturn($expected);

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class), 'Trigger the local cache');
    }

    public function identifiersRelatedProvider()
    {
        $related = new RelatedDummy();
        $related->setId($relatedId = 2);

        $dummy = new Dummy();
        $dummy->setId($id = 1);
        $dummy->setRelatedDummy($related);

        yield [$dummy, ['id' => $id, 'relatedDummy' => $relatedId]];

        $related = new RelatedDummy();
        $related->setId($relatedId = 1);

        $dummy = new Dummy();
        $dummy->setId($id = new Uuid());
        $dummy->setRelatedDummy($related);

        yield [$dummy, ['id' => $id, 'relatedDummy' => $relatedId]];

        $related = new RelatedDummy();
        $related->setId($relatedId = new Uuid());

        $dummy = new Dummy();
        $dummy->setId($id = new Uuid());
        $dummy->setRelatedDummy($related);

        yield [$dummy, ['id' => $id, 'relatedDummy' => $relatedId]];
    }

    /**
     * @dataProvider identifiersRelatedProvider
     */
    public function testFirstPassWithRelated($item, $expected)
    {
        $cacheItemKey = 'iri_identifiers'.md5(Dummy::class);
        $relatedCacheItemKey = 'iri_identifiers'.md5(RelatedDummy::class);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn(['id', 'relatedDummy']);

        $relatedCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $relatedCacheItemProphecy->isHit()->willReturn(false);

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem($cacheItemKey)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->getItem($relatedCacheItemKey)->willReturn($relatedCacheItemProphecy);

        $decoratedProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoratedProphecy->getIdentifiersFromItem($item, Argument::any())->willReturn($expected);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(RelatedDummy::class))->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');
    }

    /**
     * @dataProvider identifiersRelatedProvider
     */
    public function testSecondPassWithRelated($item, $expected)
    {
        $cacheItemKey = 'iri_identifiers'.md5(Dummy::class);
        $relatedCacheItemKey = 'iri_identifiers'.md5(RelatedDummy::class);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn(['id', 'relatedDummy']);

        $relatedCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $relatedCacheItemProphecy->isHit()->willReturn(true);
        $relatedCacheItemProphecy->get()->willReturn(['id']);

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem($cacheItemKey)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->getItem($relatedCacheItemKey)->willReturn($relatedCacheItemProphecy);

        $decoratedProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoratedProphecy->getIdentifiersFromItem($item, Argument::any())->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(RelatedDummy::class))->willReturn(RelatedDummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');
    }

    /**
     * @group legacy
     * @expectedDeprecation Not injecting ApiPlatform\Core\Api\ResourceClassResolverInterface in the CachedIdentifiersExtractor might introduce cache issues with object identifiers.
     */
    public function testDeprecationResourceClassResolver()
    {
        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);

        new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null);
    }

    /**
     * @dataProvider itemProvider
     */
    public function testPassWithContext($item, $expected)
    {
        $cacheItemKey = 'iri_identifiers'.md5(Dummy::class);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $cacheItemProphecy->set(['id'])->shouldNotBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem($cacheItemKey)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $context = ['identifiers' => ['id' => [Dummy::class, 'id']]];
        $decoratedProphecy = $this->prophesize(ContextAwareIdentifiersExtractorInterface::class);
        $decoratedProphecy->getIdentifiersFromItem($item, $context)->willReturn($expected);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Uuid::class)->willReturn(false);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item, $context));
    }
}
