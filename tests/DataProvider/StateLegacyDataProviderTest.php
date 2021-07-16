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

namespace ApiPlatform\Core\Tests\Core\Metadata;

use ApiPlatform\Core\DataProvider\StateLegacyDataProvider;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;

class StateLegacyDataProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testStateLegacyDataProviderTest()
    {
        $provider = $this->prophesize(ProviderInterface::class);
        $provider->provide('class', ['id' => 1], [])->shouldBeCalled()->willReturn('item');
        $provider->provide('class', [], [])->shouldBeCalled()->willReturn('collection');
        $legacyDataProvider = new StateLegacyDataProvider($provider->reveal());
        $this->assertEquals('item', $legacyDataProvider->getItem('class', ['id' => 1]));
        $this->assertEquals('collection', $legacyDataProvider->getCollection('class'));
    }
}
