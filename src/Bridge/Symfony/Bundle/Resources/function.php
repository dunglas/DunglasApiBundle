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

// Forward compatibility layer for the "service()" method.
// To be removed along with support for symfony/dependency-injection <5.1.

if (!\function_exists(__NAMESPACE__.'\service')) {
    eval('
        use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;
        use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;

        function service(string $serviceId): ReferenceConfigurator
        {
            return ref($serviceId);
        }
    ');
}
