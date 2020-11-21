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

use ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UlidNormalizer;
use ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UuidNormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.identifier.symfony_ulid_normalizer', UlidNormalizer::class)
            ->tag('api_platform.identifier.denormalizer')

        ->set('api_platform.identifier.symfony_uuid_normalizer', UuidNormalizer::class)
            ->tag('api_platform.identifier.denormalizer');
};
