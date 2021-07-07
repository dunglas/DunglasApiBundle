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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\DataProvider\SerializerAwareDataProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers data providers.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class DataProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach (OperationType::TYPES as $type) {
            $this->addSerializerLocator($container, $type);
        }
    }

    private function addSerializerLocator(ContainerBuilder $container, string $type): void
    {
        $services = $container->findTaggedServiceIds("api_platform.{$type}_data_provider", true);

        foreach ($services as $id => $tags) {
            $definition = $container->getDefinition((string) $id);
            if (is_a($definition->getClass(), SerializerAwareDataProviderInterface::class, true)) {
                $definition->addMethodCall('setSerializerLocator', [new Reference('api_platform.serializer_locator')]);
            }
        }
    }
}
