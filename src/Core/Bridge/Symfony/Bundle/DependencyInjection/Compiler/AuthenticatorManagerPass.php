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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Checks if the new authenticator manager exists.
 *
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class AuthenticatorManagerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('security.authenticator.manager')) {
            $container->getDefinition('api_platform.security.resource_access_checker')->setArgument(5, false);
        }
    }
}
