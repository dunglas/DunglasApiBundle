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

namespace ApiPlatform\Core\Bridge\Symfony\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
trait DispatchTrait
{
    /**
     * @var MessageBusInterface|null
     */
    private $messageBus;

    /**
     * @param object|Envelope $message
     */
    private function dispatch($message)
    {
        if (!$this->messageBus instanceof MessageBusInterface) {
            throw new \InvalidArgumentException('The message bus is not set.');
        }

        if (!class_exists(HandlerFailedException::class)) {
            return $this->messageBus->dispatch($message);
        }

        try {
            return $this->messageBus->dispatch($message);
        } catch (HandlerFailedException $e) {
            // unwrap the exception thrown in handler for Symfony Messenger >= 4.3
            while ($e instanceof HandlerFailedException) {
                /** @var \Throwable $e */
                $e = $e->getPrevious();
            }

            throw $e;
        }
    }
}
