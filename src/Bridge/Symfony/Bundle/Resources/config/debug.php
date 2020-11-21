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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataPersister\TraceableChainDataPersister;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainCollectionDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainItemDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainSubresourceDataProvider;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('debug.api_platform.collection_data_provider', TraceableChainCollectionDataProvider::class)
            ->decorate('api_platform.collection_data_provider')
            ->args([service('debug.api_platform.collection_data_provider.inner')])

        ->set('debug.api_platform.item_data_provider', TraceableChainItemDataProvider::class)
            ->decorate('api_platform.item_data_provider')
            ->args([service('debug.api_platform.item_data_provider.inner')])

        ->set('debug.api_platform.subresource_data_provider', TraceableChainSubresourceDataProvider::class)
            ->decorate('api_platform.subresource_data_provider')
            ->args([service('debug.api_platform.subresource_data_provider.inner')])

        ->set('debug.api_platform.data_persister', TraceableChainDataPersister::class)
            ->decorate('api_platform.data_persister')
            ->args([service('debug.api_platform.data_persister.inner')]);
};
