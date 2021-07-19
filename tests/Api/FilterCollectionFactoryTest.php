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

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterCollectionFactory;
use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class FilterCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testCreateFilterCollectionFromLocator()
    {
        $filter = $this->prophesize(FilterInterface::class)->reveal();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('foo')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('foo')->willReturn($filter)->shouldBeCalled();
        $filterLocatorProphecy->has('bar')->willReturn(false)->shouldBeCalled();

        $filterCollection = (new FilterCollectionFactory(['foo', 'bar']))->createFilterCollectionFromLocator($filterLocatorProphecy->reveal());

        $this->assertArrayNotHasKey('bar', $filterCollection->getArrayCopy());
        $this->assertArrayHasKey('foo', $filterCollection->getArrayCopy());
        $this->assertInstanceOf(FilterInterface::class, $filterCollection['foo']);
        $this->assertEquals(new FilterCollection(['foo' => $filter]), $filterCollection);
    }
}
