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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

final class SwaggerUiListener
{
    /**
     * Sets SwaggerUiAction as controller if the requested format is HTML.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (
            'html' !== $request->getRequestFormat('') ||
            !($request->attributes->has('_api_resource_class') || $request->attributes->getBoolean('_api_respond', false))
        ) {
            return;
        }

        $request->attributes->set('_controller', 'api_platform.swagger.action.ui');
    }
}
