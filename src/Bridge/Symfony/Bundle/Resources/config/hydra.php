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

use ApiPlatform\Core\Hydra\EventListener\AddLinkHeaderListener;
use ApiPlatform\Core\Hydra\JsonSchema\SchemaFactory;
use ApiPlatform\Core\Hydra\Serializer\CollectionFiltersNormalizer;
use ApiPlatform\Core\Hydra\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Hydra\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Core\Hydra\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Hydra\Serializer\EntrypointNormalizer;
use ApiPlatform\Core\Hydra\Serializer\ErrorNormalizer;
use ApiPlatform\Core\Hydra\Serializer\PartialCollectionViewNormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.hydra.normalizer.documentation', DocumentationNormalizer::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.resource_class_resolver'), null, ref('api_platform.router'), ref('api_platform.subresource_operation_factory'), ref('api_platform.name_converter')->ignoreOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -800])

        ->set('api_platform.hydra.listener.response.add_link_header', AddLinkHeaderListener::class)
            ->args([ref('api_platform.router')])
            ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse'])

        ->set('api_platform.hydra.normalizer.constraint_violation_list', ConstraintViolationListNormalizer::class)
            ->args([ref('api_platform.router'), '%api_platform.validator.serialize_payload_fields%', ref('api_platform.name_converter')->ignoreOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -780])

        ->set('api_platform.hydra.normalizer.entrypoint', EntrypointNormalizer::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.iri_converter'), ref('api_platform.router')])
            ->tag('serializer.normalizer', ['priority' => -800])

        ->set('api_platform.hydra.normalizer.error', ErrorNormalizer::class)
            ->args([ref('api_platform.router'), '%kernel.debug%'])
            ->tag('serializer.normalizer', ['priority' => -800])

        ->set('api_platform.hydra.normalizer.collection', CollectionNormalizer::class)
            ->args([ref('api_platform.jsonld.context_builder'), ref('api_platform.resource_class_resolver'), ref('api_platform.iri_converter')])
            ->tag('serializer.normalizer', ['priority' => -985])

        ->set('api_platform.hydra.normalizer.partial_collection_view', PartialCollectionViewNormalizer::class)
            ->decorate('api_platform.hydra.normalizer.collection')
            ->args([ref('api_platform.hydra.normalizer.partial_collection_view.inner'), '%api_platform.collection.pagination.page_parameter_name%', '%api_platform.collection.pagination.enabled_parameter_name%', ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.property_accessor')])

        ->set('api_platform.hydra.normalizer.collection_filters', CollectionFiltersNormalizer::class)
            ->decorate('api_platform.hydra.normalizer.collection')
            ->args([ref('api_platform.hydra.normalizer.collection_filters.inner'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.resource_class_resolver'), ref('api_platform.filter_locator')])

        ->set('api_platform.hydra.json_schema.schema_factory', SchemaFactory::class)
            ->decorate('api_platform.json_schema.schema_factory')
            ->args([ref('api_platform.hydra.json_schema.schema_factory.inner')]);
};
