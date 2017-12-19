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

namespace ApiPlatform\Core\Tests\HttpCache\EventListener;

use ApiPlatform\Core\HttpCache\EventListener\AddHeadersListener;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddHeadersListenerTest extends TestCase
{
    public function testDoNotSetHeaderWhenMethodNotCacheable()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $request->setMethod('PUT');

        $response = new Response();

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldNotBeCalled();

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event->reveal());

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderWhenNotAnApiOperation()
    {
        $request = new Request();
        $response = new Response();

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event->reveal());

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderWhenNoContent()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response();

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event->reveal());

        $this->assertNull($response->getEtag());
    }

    public function testAddHeaders()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response('some content', 200, ['Vary' => ['Accept', 'Cookie']]);

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, ['Accept', 'Accept-Encoding'], true);
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('"9893532233caff98cd083a116b013c0b"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
        $this->assertSame(['Accept', 'Cookie', 'Accept-Encoding'], $response->getVary());
    }
}
