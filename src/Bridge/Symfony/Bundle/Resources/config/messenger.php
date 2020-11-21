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

use ApiPlatform\Core\Bridge\Symfony\Messenger\DataPersister;
use ApiPlatform\Core\Bridge\Symfony\Messenger\DataTransformer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->alias('api_platform.message_bus', 'messenger.default_bus')

        ->set('api_platform.messenger.data_persister', DataPersister::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.message_bus'), service('api_platform.data_persister')])
            ->tag('api_platform.data_persister', ['priority' => -900])

        ->set('api_platform.messenger.data_transformer', DataTransformer::class)
            ->args([service('api_platform.metadata.resource.metadata_factory')])
            ->tag('api_platform.data_transformer', ['priority' => -10]);
};
