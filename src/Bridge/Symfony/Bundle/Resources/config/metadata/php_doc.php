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

use ApiPlatform\Core\Metadata\Resource\Factory\PhpDocResourceMetadataFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.metadata.resource.metadata_factory.php_doc', PhpDocResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 30)
            ->args([service('api_platform.metadata.resource.metadata_factory.php_doc.inner')]);
};
