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

use ApiPlatform\Core\HttpCache\EventListener\AddTagsListener;
use ApiPlatform\Core\HttpCache\VarnishPurger;
use GuzzleHttp\Client;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.http_cache.purger.varnish_client', Client::class)
            ->abstract()

        ->set('api_platform.http_cache.purger.varnish', VarnishPurger::class)

        ->set('api_platform.http_cache.listener.response.add_tags', AddTagsListener::class)
            ->args([service('api_platform.iri_converter')])
            ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse', 'priority' => -2]);
};
