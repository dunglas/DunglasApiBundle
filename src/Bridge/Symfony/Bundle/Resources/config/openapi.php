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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Command\OpenApiCommand;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Options;
use ApiPlatform\Core\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.openapi.normalizer', OpenApiNormalizer::class)
            ->args([service('serializer.normalizer.object')])
            ->tag('serializer.normalizer', ['priority' => -785])

        ->alias(OpenApiNormalizer::class, 'api_platform.openapi.normalizer')

        ->set('api_platform.openapi.options', Options::class)
            ->args(['%api_platform.title%', '%api_platform.description%', '%api_platform.version%', '%api_platform.oauth.enabled%', '%api_platform.oauth.type%', '%api_platform.oauth.flow%', '%api_platform.oauth.tokenUrl%', '%api_platform.oauth.authorizationUrl%', '%api_platform.oauth.refreshUrl%', '%api_platform.oauth.scopes%', '%api_platform.swagger.api_keys%'])

        ->alias(Options::class, 'api_platform.openapi.options')

        ->set('api_platform.openapi.factory', OpenApiFactory::class)
            ->args([service('api_platform.metadata.resource.name_collection_factory'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.json_schema.schema_factory'), service('api_platform.json_schema.type_factory'), service('api_platform.operation_path_resolver'), service('api_platform.filter_locator'), service('api_platform.subresource_operation_factory'), '%api_platform.formats%', service('api_platform.openapi.options'), service('api_platform.pagination_options')])

        ->alias(OpenApiFactoryInterface::class, 'api_platform.openapi.factory')

        ->set('api_platform.openapi.command', OpenApiCommand::class)
            ->args([service('api_platform.openapi.factory'), service('api_platform.serializer')])
            ->tag('console.command')

        ->set('api_platform.openapi.normalizer.api_gateway', ApiGatewayNormalizer::class)
            ->decorate('api_platform.openapi.normalizer', null, -1)
            ->args([service('api_platform.openapi.normalizer.api_gateway.inner')])
            ->tag('serializer.normalizer', ['priority' => -780]);
};
