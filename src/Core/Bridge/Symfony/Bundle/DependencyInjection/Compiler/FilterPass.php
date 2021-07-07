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

use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects filters.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class FilterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(ContainerBuilder $container)
    {
        $filters = [];
        foreach ($container->findTaggedServiceIds('api_platform.filter', true) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['id'])) {
                    $tag['id'] = $serviceId;
                }

                $filters[$tag['id']] = new Reference($serviceId);
            }
        }

        $container->getDefinition('api_platform.filter_locator')->addArgument($filters);
        $container->getDefinition('api_platform.filter_collection_factory')->addArgument(array_keys($filters));
    }
}
