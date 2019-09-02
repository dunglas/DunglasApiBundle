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

namespace ApiPlatform\Core\Tests\HttpCache;

use ApiPlatform\Core\HttpCache\VarnishPurger;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class VarnishPurgerTest extends TestCase
{
    public function testPurge(): void
    {
        $this->doTestPurge(
            $this->prophesize(HttpClientInterface::class),
            $this->prophesize(HttpClientInterface::class)
        );
    }

    public function testLegacyPurge(): void
    {
        $this->doTestPurge(
            $this->prophesize(ClientInterface::class),
            $this->prophesize(ClientInterface::class)
        );
    }

    private function doTestPurge(ObjectProphecy $clientProphecy1, ObjectProphecy $clientProphecy2): void
    {
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->shouldBeCalled();
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->shouldBeCalled();

        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->shouldBeCalled();
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->shouldBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);
    }

    public function testEmptyTags(): void
    {
        $this->doTestEmptyTags( $this->prophesize(HttpClientInterface::class));
    }

    public function testLegacyEmptyTags(): void
    {
        $this->doTestEmptyTags( $this->prophesize(ClientInterface::class));
    }

    private function doTestEmptyTags(ObjectProphecy $clientProphecy1): void
    {
        $clientProphecy1->request()->shouldNotBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal()]);
        $purger->purge([]);
    }
}
