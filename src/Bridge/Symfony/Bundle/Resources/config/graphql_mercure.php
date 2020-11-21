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

use ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGenerator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.graphql.subscription.mercure_iri_generator', MercureSubscriptionIriGenerator::class)
            ->args([service('router.request_context')]);
};
