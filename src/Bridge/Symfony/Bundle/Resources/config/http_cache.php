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

use ApiPlatform\Core\HttpCache\EventListener\AddHeadersListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.http_cache.listener.response.configure', AddHeadersListener::class)
            ->args(['%api_platform.http_cache.etag%', '%api_platform.http_cache.max_age%', '%api_platform.http_cache.shared_max_age%', '%api_platform.http_cache.vary%', '%api_platform.http_cache.public%', service('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse', 'priority' => -1]);
};
