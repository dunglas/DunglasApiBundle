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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Cache;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Cache\QueryExpander;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;

/**
 * @author st-it <33101537+st-it@users.noreply.github.com>
 */
class QueryExpanderTest extends TestCase
{
    public function testNoCacheConfig()
    {
        $resourceMetadata = new ResourceMetadata('Dummy');
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->setCacheable()->shouldNotBeCalled();
        $query->setHint()->shouldNotBeCalled();
        $query->setCacheMode()->shouldNotBeCalled();
        $query->useResultCache()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testSetCacheable()
    {
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [QueryExpander::CACHEABLE_ATTR => true],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->setCacheable(true)->shouldBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testNoCacheableAttr()
    {
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->setCacheable()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testSetCacheHint()
    {
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [QueryExpander::CACHE_HINT_ATTR => [
                Query::HINT_CACHE_EVICT => true,
                Query::HINT_REFRESH => false,
            ]],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->setHint(Query::HINT_CACHE_EVICT, true)->shouldBeCalled();
        $query->setHint(Query::HINT_REFRESH, false)->shouldBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testNoCacheHint()
    {
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->setHint()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testSetCacheMode()
    {
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [QueryExpander::CACHE_MODE_ATTR => Cache::MODE_REFRESH],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->setCacheMode(Cache::MODE_REFRESH)->shouldBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testNoCacheModeAttr()
    {
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->setCacheMode()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testUseResultCache()
    {
        $resultCacheArgs = [true, 3600, 'id'];
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [QueryExpander::USE_RESULT_CACHE_ATTR => $resultCacheArgs],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->useResultCache(...$resultCacheArgs)->shouldBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testNoResultCacheAttr()
    {
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->useResultCache()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testResultCacheAttrInvalidValue()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/^Attribute value [a-z_]* should be an array$/');
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [QueryExpander::USE_RESULT_CACHE_ATTR => 'not an array'],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->useResultCache()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testResultCacheAttrEmptyArgs()
    {
        $resultCacheArgs = [];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/^Attribute [a-z_]* should at least contain one item for use. Other options are lifetime and and result cache id$/');
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [QueryExpander::USE_RESULT_CACHE_ATTR => $resultCacheArgs],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->useResultCache()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }

    public function testResultCacheAttrTooMuchArgs()
    {
        $resultCacheArgs = [true, 3600, 'id', 'one_too_much'];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/^Attribute [a-z_]* should at least contain one item for use. Other options are lifetime and and result cache id$/');
        $resourceMetadata = new ResourceMetadata('Dummy', null, null, null, null, [
            QueryExpander::DOCTRINE_CACHE_CONFIG_ATTR => [QueryExpander::USE_RESULT_CACHE_ATTR => $resultCacheArgs],
        ]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $query = $this->prophesize(AbstractQuery::class);
        $query->useResultCache()->shouldNotBeCalled();

        $queryExpander = new QueryExpander($resourceMetadataFactoryProphecy->reveal());

        $queryExpander->expand(Dummy::class, $query->reveal());
    }
}
