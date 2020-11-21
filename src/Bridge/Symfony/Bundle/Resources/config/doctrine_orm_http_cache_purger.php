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

use ApiPlatform\Core\Bridge\Doctrine\EventListener\PurgeHttpCacheListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.doctrine.listener.http_cache.purge', PurgeHttpCacheListener::class)
            ->args([service('api_platform.http_cache.purger'), service('api_platform.iri_converter'), service('api_platform.resource_class_resolver')])
            ->tag('doctrine.event_listener', ['event' => 'preUpdate'])
            ->tag('doctrine.event_listener', ['event' => 'onFlush'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush']);
};
