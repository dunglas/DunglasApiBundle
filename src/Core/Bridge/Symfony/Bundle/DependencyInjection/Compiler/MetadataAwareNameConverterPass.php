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
 * Injects the metadata aware name converter if available.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class MetadataAwareNameConverterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('serializer.name_converter.metadata_aware')) {
            return;
        }

        $definition = $container->getDefinition('serializer.name_converter.metadata_aware');
        $num = \count($definition->getArguments());

        if ($container->hasAlias('api_platform.name_converter')) {
            $nameConverter = new Reference((string) $container->getAlias('api_platform.name_converter'));
            if (1 === $num) {
                $definition->addArgument($nameConverter);
            } elseif (1 < $num && null === $definition->getArgument(1)) {
                $definition->setArgument(1, $nameConverter);
            }
        }

        $container->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware');
    }
}
