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

use ApiPlatform\Core\DataPersister\ChainDataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.data_persister', ChainDataPersister::class)
            ->args([tagged_iterator('api_platform.data_persister')])
        ->alias(DataPersisterInterface::class, 'api_platform.data_persister');
};
