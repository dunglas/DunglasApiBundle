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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Util\ErrorFormatGuesser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles validation errors.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationExceptionListener
{
    private $serializer;
    private $errorFormats;
    private $exceptionToStatus;

    public function __construct(SerializerInterface $serializer, array $errorFormats, array $exceptionToStatus = [])
    {
        $this->serializer = $serializer;
        $this->errorFormats = $errorFormats;
        $this->exceptionToStatus = $exceptionToStatus;
    }

    /**
     * Returns a list of violations normalized in the Hydra format.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = method_exists($event, 'getThrowable') ? $event->getThrowable() : $event->getException(); // @phpstan-ignore-line
        if (!$exception instanceof ConstraintViolationListAwareExceptionInterface && !$exception instanceof FilterValidationException) {
            return;
        }
        $exceptionClass = \get_class($exception);
        $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

        foreach ($this->exceptionToStatus as $class => $status) {
            if (is_a($exceptionClass, $class, true)) {
                $statusCode = $status;

                break;
            }
        }

        $format = ErrorFormatGuesser::guessErrorFormat($event->getRequest(), $this->errorFormats);

        $event->setResponse(new Response(
                $this->serializer->serialize($exception instanceof ConstraintViolationListAwareExceptionInterface ? $exception->getConstraintViolationList() : $exception, $format['key']),
                $statusCode,
                [
                    'Content-Type' => sprintf('%s; charset=utf-8', $format['value'][0]),
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'deny',
                ]
        ));
    }
}
