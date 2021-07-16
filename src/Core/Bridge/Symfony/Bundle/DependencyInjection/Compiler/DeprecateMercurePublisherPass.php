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

use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Handles Mercure Publisher depreciation.
 *
 * @internal calls `setDeprecated` method with valid arguments
 *  depending which version of symfony/dependency-injection is used
 */
final class DeprecateMercurePublisherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container
            ->setAlias('api_platform.doctrine.listener.mercure.publish', 'api_platform.doctrine.orm.listener.mercure.publish')
            ->setDeprecated(...$this->buildDeprecationArgs('2.6', 'Using "%alias_id%" service is deprecated since API Platform 2.6. Use "api_platform.doctrine.orm.listener.mercure.publish" instead.'));
    }

    private function buildDeprecationArgs(string $version, string $message): array
    {
        return method_exists(BaseNode::class, 'getDeprecation')
            ? ['api-platform/core', $version, $message]
            : [$message];
    }
}
