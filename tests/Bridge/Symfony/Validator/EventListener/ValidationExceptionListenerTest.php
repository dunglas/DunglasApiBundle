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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\EventListener\ValidationExceptionListener;
use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidationExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNotValidationException()
    {
        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getException()->willReturn(new \Exception())->shouldBeCalled();
        $eventProphecy->setResponse()->shouldNotBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $listener->onKernelException($eventProphecy->reveal());
    }

    public function testValidationException()
    {
        $list = new ConstraintViolationList([]);

        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getException()->willReturn(new ValidationException($list))->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();
        $eventProphecy->setResponse(new Response('{"foo": "bar"}', Response::HTTP_BAD_REQUEST, [
            'Content-Type' => 'application/ld+json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ]))->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($list, 'hydra')->willReturn('{"foo": "bar"}')->shouldBeCalled();

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $listener->onKernelException($eventProphecy->reveal());
    }

    public function testValidationUniqueViolationException()
    {
        $violation = new ConstraintViolation(
            'jordan already exists',
            'this is the message template',
            [],
            ['jordan' => 42],
            'jordan',
            null,
            null,
            UniqueEntity::NOT_UNIQUE_ERROR
        );

        $list = new ConstraintViolationList([$violation]);

        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getException()->willReturn(new ValidationException($list))->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();
        $eventProphecy->setResponse(new Response('{"foo": "bar"}', Response::HTTP_CONFLICT, [
            'Content-Type' => 'application/ld+json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ]))->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($list, 'hydra')->willReturn('{"foo": "bar"}')->shouldBeCalled();

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $listener->onKernelException($eventProphecy->reveal());
    }

    public function testValidationMultipleViolationException()
    {
        $violation1 = new ConstraintViolation(
            'jordan already exists',
            'this is the message template',
            [],
            ['jordan' => 42],
            'jordan',
            null,
            null,
            UniqueEntity::NOT_UNIQUE_ERROR
        );

        $violation2 = new ConstraintViolation(
            'montreal already exists',
            'this is the message template',
            [],
            ['montreal' => 42],
            'montreal',
            null,
            null,
            NotBlank::IS_BLANK_ERROR
        );

        $list = new ConstraintViolationList([$violation1, $violation2]);

        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getException()->willReturn(new ValidationException($list))->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();
        $eventProphecy->setResponse(new Response('{"foo": "bar"}', Response::HTTP_BAD_REQUEST, [
            'Content-Type' => 'application/ld+json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ]))->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($list, 'hydra')->willReturn('{"foo": "bar"}')->shouldBeCalled();

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $listener->onKernelException($eventProphecy->reveal());
    }
}
