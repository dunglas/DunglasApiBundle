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

use ApiPlatform\Core\Security\Core\Authorization\ExpressionLanguageProvider;
use ApiPlatform\Core\Security\EventListener\DenyAccessListener;
use ApiPlatform\Core\Security\ResourceAccessChecker;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->alias('api_platform.security.expression_language', 'security.expression_language')

        ->set('api_platform.security.resource_access_checker', ResourceAccessChecker::class)
            ->args([service('api_platform.security.expression_language')->nullOnInvalid(), service('security.authentication.trust_resolver')->nullOnInvalid(), service('security.role_hierarchy')->nullOnInvalid(), service('security.token_storage')->nullOnInvalid(), service('security.authorization_checker')->nullOnInvalid()])
        ->alias(ResourceAccessCheckerInterface::class, 'api_platform.security.resource_access_checker')

        ->set('api_platform.security.listener.request.deny_access', DenyAccessListener::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.security.resource_access_checker')])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onSecurity', 'priority' => 3])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onSecurityPostDenormalize', 'priority' => 1])

        ->set('api_platform.security.expression_language_provider', ExpressionLanguageProvider::class)
            ->tag('security.expression_language_provider');
};
