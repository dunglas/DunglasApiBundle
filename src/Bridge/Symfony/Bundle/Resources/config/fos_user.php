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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ApiPlatform\Core\Bridge\FosUser\EventListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.fos_user.event_listener', EventListener::class)
            ->args([service('fos_user.user_manager')])
            ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 24]);
};
