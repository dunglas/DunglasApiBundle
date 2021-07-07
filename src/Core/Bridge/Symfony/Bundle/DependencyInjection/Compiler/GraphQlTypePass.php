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
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects GraphQL types.
 *
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class GraphQlTypePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('api_platform.graphql.enabled')) {
            return;
        }

        $types = [];
        foreach ($container->findTaggedServiceIds('api_platform.graphql.type', true) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $types[$tag['id'] ?? $serviceId] = new Reference($serviceId);
            }
        }

        $container->getDefinition('api_platform.graphql.type_locator')->addArgument($types);
        $container->getDefinition('api_platform.graphql.types_factory')->addArgument(array_keys($types));
    }
}
