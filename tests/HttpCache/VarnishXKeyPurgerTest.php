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

use ApiPlatform\Core\HttpCache\VarnishXKeyPurger;
use ApiPlatform\Core\Tests\ProphecyTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class VarnishXKeyPurgerTest extends TestCase
{
    use ProphecyTrait;

    public function testPurge()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request('PURGE', '', ['headers' => ['xkey' => '/foo']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy1->request('PURGE', '', ['headers' => ['xkey' => '/foo /bar']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy2 = $this->prophesize(ClientInterface::class);
        $clientProphecy2->request('PURGE', '', ['headers' => ['xkey' => '/foo']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy2->request('PURGE', '', ['headers' => ['xkey' => '/foo /bar']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy3 = $this->prophesize(ClientInterface::class);
        $clientProphecy3->request('PURGE', '', ['headers' => ['xkey' => '/foo /bar']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy4 = $this->prophesize(ClientInterface::class);
        $clientProphecy4->request('PURGE', '', ['headers' => ['xkey' => '/foo /bar']])->willReturn(new Response())->shouldBeCalled();

        $purger = new VarnishXKeyPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);

        $purger = new VarnishXKeyPurger([$clientProphecy3->reveal(), $clientProphecy4->reveal()], 12);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);
    }

    public function testEmptyTags()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request()->shouldNotBeCalled();

        $purger = new VarnishXKeyPurger([$clientProphecy1->reveal()]);
        $purger->purge([]);
    }

    public function testHeaderTooLong()
    {
        $this->expectExceptionMessage('IRI "/foobar-long-foobar-toolong-foofoo-barbar" is too long to fit current max header length (currently set to "20"). You can increase it using the "api_platform.http_cache.invalidation.max_header_length" parameter.');

        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request('PURGE', '', ['headers' => ['xkey' => '/foobar-long-foobar-toolong-foofoo-barbar']])->willReturn(new Response())->shouldNotBeCalled();

        $purger = new VarnishXKeyPurger([$clientProphecy1->reveal()], 20);
        $purger->purge(['/foobar-long-foobar-toolong-foofoo-barbar']);
    }

    public function testCustomGlue()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request('PURGE', '', ['headers' => ['xkey' => '/foo,/bar,/baz']])->willReturn(new Response())->shouldBeCalled();

        $purger = new VarnishXKeyPurger([$clientProphecy1->reveal()], 50, ',');
        $purger->purge(['/foo', '/bar', '/baz']);
    }

    /**
     * @dataProvider provideChunkHeaderCases
     */
    public function testItChunksHeaderToAvoidHittingVarnishLimit(int $maxHeaderLength, array $iris, array $keysToSend)
    {
        $client = new class() implements ClientInterface {
            public $sentKeys = [];

            public function send(RequestInterface $request, array $options = []): ResponseInterface
            {
                throw new LogicException('Not implemented');
            }

            public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
            {
                throw new LogicException('Not implemented');
            }

            public function request($method, $uri, array $options = []): ResponseInterface
            {
                $this->sentKeys[] = $options['headers']['xkey'];

                return new Response();
            }

            public function requestAsync($method, $uri, array $options = []): PromiseInterface
            {
                throw new LogicException('Not implemented');
            }

            public function getConfig($option = null)
            {
                throw new LogicException('Not implemented');
            }
        };

        $purger = new VarnishXKeyPurger([$client], $maxHeaderLength);
        $purger->purge($iris);

        self::assertSame($keysToSend, $client->sentKeys);
    }

    public function provideChunkHeaderCases()
    {
        yield 'no iri' => [
            50,
            [],
            [],
        ];

        yield 'one iri' => [
            50,
            ['/foo'],
            ['/foo'],
        ];

        yield 'few iris' => [
            50,
            ['/foo', '/bar', '/ab', '/cd'],
            ['/foo /bar /ab /cd'],
        ];

        yield 'iris to generate a header with exactly the maximum length' => [
            27,
            ['/foofoofoofoo', '/barbarbarbar'],
            ['/foofoofoofoo /barbarbarbar'],
        ];

        yield 'iris to generate a header with exactly the maximum length and a smaller one' => [
            21,
            ['/foobarfoo', '/barfoobar', '/baz'],
            [
                '/foobarfoo /barfoobar',
                '/baz',
            ],
        ];

        yield 'with last iri too long to be part of the same header' => [
            18,
            ['/foo', '/bar', '/some-longer-tag'],
            [
                '/foo /bar',
                '/some-longer-tag',
            ],
        ];

        yield 'iris to have five headers' => [
            13,
            ['/foo/1', '/foo/2', '/foo/3', '/foo/4', '/foo/5', '/foo/6', '/foo/7', '/foo/8', '/foo/9', '/foo/a'],
            ['/foo/1 /foo/2', '/foo/3 /foo/4', '/foo/5 /foo/6', '/foo/7 /foo/8', '/foo/9 /foo/a'],
        ];

        yield 'iris to have three headers of same size and one bigger' => [
            13,
            ['/foo/1', '/foo/2', '/foo/3', '/foo/4', '/foo/5', '/foo/6', '/foo/8910'],
            ['/foo/1 /foo/2', '/foo/3 /foo/4', '/foo/5 /foo/6', '/foo/8910'],
        ];

        yield 'with varnish default limit' => [
            8000,
            array_fill(0, 3002, '/foo'),
            [
                implode(' ', array_fill(0, 1600, '/foo')),
                implode(' ', array_fill(0, 1402, '/foo')),
            ],
        ];
    }
}
