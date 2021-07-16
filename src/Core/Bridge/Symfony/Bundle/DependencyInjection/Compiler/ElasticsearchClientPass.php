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

use Elasticsearch\ClientBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates the Elasticsearch client.
 *
 * @author Baptiste Meyer <baptiste@les-tilleuls.coop>
 */
final class ElasticsearchClientPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('api_platform.elasticsearch.enabled')) {
            return;
        }

        $clientConfiguration = [];

        if ($hosts = $container->getParameter('api_platform.elasticsearch.hosts')) {
            $clientConfiguration['hosts'] = $hosts;
        }

        if ($container->has('logger')) {
            $clientConfiguration['logger'] = new Reference('logger');
            $clientConfiguration['tracer'] = new Reference('logger');
        }

        $clientDefinition = $container->getDefinition('api_platform.elasticsearch.client');

        if (!$clientConfiguration) {
            $clientDefinition->setFactory([ClientBuilder::class, 'build']);
        } else {
            $clientDefinition->setFactory([ClientBuilder::class, 'fromConfig']);
            $clientDefinition->setArguments([$clientConfiguration]);
        }
    }
}
