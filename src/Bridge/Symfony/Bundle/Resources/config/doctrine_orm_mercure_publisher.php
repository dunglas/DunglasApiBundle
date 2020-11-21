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

use ApiPlatform\Core\Bridge\Doctrine\EventListener\PublishMercureUpdatesListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.doctrine.orm.listener.mercure.publish', PublishMercureUpdatesListener::class)
            ->args([service('api_platform.resource_class_resolver'), service('api_platform.iri_converter'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.serializer'), '%api_platform.formats%', service('messenger.default_bus')->ignoreOnInvalid(), service('mercure.hub.default.publisher'), service('api_platform.graphql.subscription.subscription_manager')->ignoreOnInvalid(), service('api_platform.graphql.subscription.mercure_iri_generator')->ignoreOnInvalid()])
            ->tag('doctrine.event_listener', ['event' => 'onFlush'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush']);
};
