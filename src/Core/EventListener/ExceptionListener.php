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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as LegacyExceptionListener;

/**
 * Handles requests errors.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ExceptionListener
{
    /**
     * @var ErrorListener
     */
    private $exceptionListener;

    public function __construct($controller, LoggerInterface $logger = null, $debug = false, ErrorListener $errorListener = null)
    {
        $this->exceptionListener = $errorListener ? new ErrorListener($controller, $logger, $debug) : new LegacyExceptionListener($controller, $logger, $debug); // @phpstan-ignore-line
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        // Normalize exceptions only for routes managed by API Platform
        if (
            'html' === $request->getRequestFormat('') ||
            !((RequestAttributesExtractor::extractAttributes($request)['respond'] ?? $request->attributes->getBoolean('_api_respond', false)) || $request->attributes->getBoolean('_graphql', false))
        ) {
            return;
        }

        $this->exceptionListener->onKernelException($event);
    }
}
