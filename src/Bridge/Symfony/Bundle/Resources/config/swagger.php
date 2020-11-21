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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Command\SwaggerCommand;
use ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.swagger.normalizer.documentation', DocumentationNormalizer::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.json_schema.schema_factory'), service('api_platform.json_schema.type_factory'), service('api_platform.operation_path_resolver'), null, service('api_platform.filter_locator'), null, '%api_platform.oauth.enabled%', '%api_platform.oauth.type%', '%api_platform.oauth.flow%', '%api_platform.oauth.tokenUrl%', '%api_platform.oauth.authorizationUrl%', '%api_platform.oauth.scopes%', '%api_platform.swagger.api_keys%', service('api_platform.subresource_operation_factory'), '%api_platform.collection.pagination.enabled%', '%api_platform.collection.pagination.page_parameter_name%', '%api_platform.collection.pagination.client_items_per_page%', '%api_platform.collection.pagination.items_per_page_parameter_name%', '%api_platform.formats%', '%api_platform.collection.pagination.client_enabled%', '%api_platform.collection.pagination.enabled_parameter_name%', [], '%api_platform.swagger.versions%'])
            ->tag('serializer.normalizer', ['priority' => -790])

        ->set('api_platform.swagger.normalizer.api_gateway', ApiGatewayNormalizer::class)
            ->decorate('api_platform.swagger.normalizer.documentation', null, -1)
            ->args([service('api_platform.swagger.normalizer.api_gateway.inner')])
            ->tag('serializer.normalizer', ['priority' => -780])

        ->set('api_platform.swagger.command.swagger_command', SwaggerCommand::class)
            ->args([service('api_platform.serializer'), service('api_platform.metadata.resource.name_collection_factory'), '%api_platform.title%', '%api_platform.description%', '%api_platform.version%', null, '%api_platform.swagger.versions%'])
            ->tag('console.command');
};
