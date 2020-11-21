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

use ApiPlatform\Core\Problem\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Core\Problem\Serializer\ErrorNormalizer;
use ApiPlatform\Core\Serializer\JsonEncoder;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.problem.encoder', JsonEncoder::class)
            ->args(['jsonproblem'])
            ->tag('serializer.encoder')

        ->set('api_platform.problem.normalizer.constraint_violation_list', ConstraintViolationListNormalizer::class)
            ->args(['%api_platform.validator.serialize_payload_fields%', service('api_platform.name_converter')->ignoreOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -780])

        ->set('api_platform.problem.normalizer.error', ErrorNormalizer::class)
            ->args(['%kernel.debug%'])
            ->tag('serializer.normalizer', ['priority' => -810]);
};
