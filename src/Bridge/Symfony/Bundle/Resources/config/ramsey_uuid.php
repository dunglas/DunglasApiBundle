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

use ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer;
use ApiPlatform\Core\Bridge\RamseyUuid\Serializer\UuidDenormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.identifier.uuid_normalizer', UuidNormalizer::class)
            ->tag('api_platform.identifier.denormalizer')

        ->set('api_platform.serializer.uuid_denormalizer', UuidDenormalizer::class)
            ->tag('serializer.normalizer');
};
