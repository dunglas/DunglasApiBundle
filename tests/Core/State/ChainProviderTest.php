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

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\State\ChainProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;

class ChainProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testChainProvider()
    {
        $a = $this->prophesize(ProviderInterface::class);
        $a->supports('class', [], [])->willReturn(false);
        $a->provide('class', [], [])->shouldNotBeCalled();

        $b = $this->prophesize(ProviderInterface::class);
        $b->supports('class', [], [])->willReturn(true);
        $b->provide('class', [], [])->willReturn('value');

        $chainProvider = new ChainProvider([
            $a->reveal(),
            $b->reveal(),
        ]);

        $this->assertEquals('value', $chainProvider->provide('class', [], []));
        $this->assertTrue($chainProvider->supports('class', [], []));
    }

    /**
     * @requires PHP 8.0
     */
    public function testReturnValueWhenNoProvider()
    {
        $a = $this->prophesize(ProviderInterface::class);
        $a->supports('class', ['id' => 1], [])->willReturn(false);
        $a->supports('class', [], [])->willReturn(false);
        $a->provide('class', ['id' => 1], [])->shouldNotBeCalled();
        $a->provide('class', [], [])->shouldNotBeCalled();

        $chainProvider = new ChainProvider([
            $a->reveal(),
        ]);

        $this->assertNull($chainProvider->provide('class', ['id' => 1], []));
        $this->assertEquals([], $chainProvider->provide('class', [], []));
        $this->assertFalse($chainProvider->supports('class', [], []));
    }
}
