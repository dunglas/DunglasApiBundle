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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\HttpClientTrait;

final class TestClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (
            !class_exists(AbstractBrowser::class) ||
            !trait_exists(HttpClientTrait::class) ||
            !$container->hasParameter('test.client.parameters')
        ) {
            return;
        }

        $container->setDefinition(
            'test.api_platform.client',
            (new Definition(Client::class, [new Reference('test.client')]))
                ->setShared(false)
                ->setPublic(true)
        );
    }
}
