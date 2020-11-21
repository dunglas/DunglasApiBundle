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

use ApiPlatform\Core\Action\EntrypointAction;
use ApiPlatform\Core\Action\ExceptionAction;
use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Action\PlaceholderAction;
use ApiPlatform\Core\Api\CachedIdentifiersExtractor;
use ApiPlatform\Core\Api\FormatsProvider;
use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface;
use ApiPlatform\Core\Api\ResourceClassResolver;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Symfony\Bundle\CacheWarmer\CachePoolClearerCacheWarmer;
use ApiPlatform\Core\Bridge\Symfony\Routing\ApiLoader;
use ApiPlatform\Core\Bridge\Symfony\Routing\CachedRouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\Bridge\Symfony\Routing\OperationMethodResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\Router;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouterOperationPathResolver;
use ApiPlatform\Core\Bridge\Symfony\Validator\EventListener\ValidationExceptionListener;
use ApiPlatform\Core\Documentation\Action\DocumentationAction;
use ApiPlatform\Core\EventListener\AddFormatListener;
use ApiPlatform\Core\EventListener\DeserializeListener;
use ApiPlatform\Core\EventListener\ExceptionListener;
use ApiPlatform\Core\EventListener\ReadListener;
use ApiPlatform\Core\EventListener\RespondListener;
use ApiPlatform\Core\EventListener\SerializeListener;
use ApiPlatform\Core\EventListener\WriteListener;
use ApiPlatform\Core\Identifier\IdentifierConverter;
use ApiPlatform\Core\Identifier\Normalizer\DateTimeIdentifierDenormalizer;
use ApiPlatform\Core\Identifier\Normalizer\IntegerDenormalizer;
use ApiPlatform\Core\Operation\DashPathSegmentNameGenerator;
use ApiPlatform\Core\Operation\Factory\CachedSubresourceOperationFactory;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactory;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\DashOperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolver;
use ApiPlatform\Core\PathResolver\UnderscoreOperationPathResolver;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use ApiPlatform\Core\Serializer\ItemNormalizer;
use ApiPlatform\Core\Serializer\SerializerContextBuilder;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Serializer\SerializerFilterContextBuilder;
use Negotiation\Negotiator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->alias('api_platform.serializer', 'serializer')
        ->alias('api_platform.property_accessor', 'property_accessor')
        ->alias('api_platform.property_info', 'property_info')

        ->set('api_platform.negotiator', Negotiator::class)

        ->set('api_platform.resource_class_resolver', ResourceClassResolver::class)
            ->args([service('api_platform.metadata.resource.name_collection_factory')])
        ->alias(ResourceClassResolverInterface::class, 'api_platform.resource_class_resolver')

        ->set('api_platform.operation_method_resolver', OperationMethodResolver::class)
            ->args([service('api_platform.router'), service('api_platform.metadata.resource.metadata_factory')])

        ->set('api_platform.route_name_resolver', RouteNameResolver::class)
            ->args([service('api_platform.router')])

        ->set('api_platform.route_name_resolver.cached', CachedRouteNameResolver::class)
            ->decorate('api_platform.route_name_resolver', null, -10)
            ->args([service('api_platform.cache.route_name_resolver'), service('api_platform.route_name_resolver.cached.inner')])

        ->set('api_platform.route_loader', ApiLoader::class)
            ->args([service('kernel'), service('api_platform.metadata.resource.name_collection_factory'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.operation_path_resolver.custom'), service('service_container'), '%api_platform.formats%', '%api_platform.resource_class_directories%', service('api_platform.subresource_operation_factory'), '%api_platform.graphql.enabled%', '%api_platform.enable_entrypoint%', '%api_platform.enable_docs%', '%api_platform.graphql.graphiql.enabled%', '%api_platform.graphql.graphql_playground.enabled%'])
            ->tag('routing.loader')

        ->alias(UrlGeneratorInterface::class, 'api_platform.router')

        ->set('api_platform.router', Router::class)
            ->args([service('router'), '%api_platform.url_generation_strategy%'])

        ->set('api_platform.iri_converter', IriConverter::class)
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.item_data_provider'), service('api_platform.route_name_resolver'), service('api_platform.router'), service('api_platform.property_accessor'), service('api_platform.identifiers_extractor.cached'), service('api_platform.subresource_data_provider')->ignoreOnInvalid(), service('api_platform.identifier.converter')->ignoreOnInvalid(), service('api_platform.resource_class_resolver'), service('api_platform.metadata.resource.metadata_factory')])
        ->alias(IriConverterInterface::class, 'api_platform.iri_converter')

        ->set('api_platform.formats_provider', FormatsProvider::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), '%api_platform.formats%'])
        ->alias(OperationAwareFormatsProviderInterface::class, 'api_platform.formats_provider')

        ->set('api_platform.serializer.context_builder', SerializerContextBuilder::class)
            ->args([service('api_platform.metadata.resource.metadata_factory')])
        ->alias(SerializerContextBuilderInterface::class, 'api_platform.serializer.context_builder')

        ->set('api_platform.serializer.context_builder.filter', SerializerFilterContextBuilder::class)
            ->decorate('api_platform.serializer.context_builder')
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.filter_locator'), service('api_platform.serializer.context_builder.filter.inner')])

        ->set('api_platform.serializer.property_filter', PropertyFilter::class)
            ->abstract()
            ->args(['$parameterName' => 'properties', '$overrideDefaultProperties' => false, '$whitelist' => null, '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(PropertyFilter::class, 'api_platform.serializer.property_filter')

        ->set('api_platform.serializer.group_filter', GroupFilter::class)
            ->abstract()
        ->alias(GroupFilter::class, 'api_platform.serializer.group_filter')

        ->set('api_platform.serializer.normalizer.item', ItemNormalizer::class)
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.iri_converter'), service('api_platform.resource_class_resolver'), service('api_platform.property_accessor'), service('api_platform.name_converter')->ignoreOnInvalid(), service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(), service('api_platform.item_data_provider')->ignoreOnInvalid(), '%api_platform.allow_plain_identifiers%', null, tagged_iterator('api_platform.data_transformer'), service('api_platform.metadata.resource.metadata_factory')->ignoreOnInvalid(), service('api_platform.security.resource_access_checker')])
            ->tag('serializer.normalizer', ['priority' => -895])

        ->alias('api_platform.operation_path_resolver', 'api_platform.operation_path_resolver.router')

        ->set('api_platform.operation_path_resolver.router', RouterOperationPathResolver::class)
            ->args([service('api_platform.router'), service('api_platform.operation_path_resolver.custom'), service('api_platform.subresource_operation_factory')])

        ->set('api_platform.operation_path_resolver.custom', CustomOperationPathResolver::class)
            ->args([service('api_platform.operation_path_resolver.generator')])

        ->set('api_platform.operation_path_resolver.generator', OperationPathResolver::class)
            ->args([service('api_platform.path_segment_name_generator')])

        ->set('api_platform.operation_path_resolver.underscore', UnderscoreOperationPathResolver::class)

        ->set('api_platform.operation_path_resolver.dash', DashOperationPathResolver::class)

        ->set('api_platform.path_segment_name_generator.underscore', UnderscorePathSegmentNameGenerator::class)

        ->set('api_platform.path_segment_name_generator.dash', DashPathSegmentNameGenerator::class)

        ->set('api_platform.listener.request.add_format', AddFormatListener::class)
            ->args([service('api_platform.negotiator'), service('api_platform.metadata.resource.metadata_factory'), '%api_platform.formats%'])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 7])

        ->set('api_platform.listener.request.read', ReadListener::class)
            ->args([service('api_platform.collection_data_provider'), service('api_platform.item_data_provider'), service('api_platform.subresource_data_provider'), service('api_platform.serializer.context_builder'), service('api_platform.identifier.converter'), service('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 4])

        ->set('api_platform.listener.view.write', WriteListener::class)
            ->args([service('api_platform.data_persister'), service('api_platform.iri_converter'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.resource_class_resolver')])
            ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 32])

        ->set('api_platform.listener.request.deserialize', DeserializeListener::class)
            ->args([service('api_platform.serializer'), service('api_platform.serializer.context_builder'), service('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 2])

        ->set('api_platform.listener.view.serialize', SerializeListener::class)
            ->args([service('api_platform.serializer'), service('api_platform.serializer.context_builder'), service('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 16])

        ->set('api_platform.listener.view.respond', RespondListener::class)
            ->args([service('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 8])

        ->set('api_platform.listener.exception.validation', ValidationExceptionListener::class)
            ->args([service('api_platform.serializer'), '%api_platform.error_formats%', '%api_platform.exception_to_status%'])
            ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'method' => 'onKernelException'])

        ->set('api_platform.listener.exception', ExceptionListener::class)
            ->args(['api_platform.action.exception', service('logger')->nullOnInvalid(), false, service('exception_listener')->nullOnInvalid()])
            ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'method' => 'onKernelException', 'priority' => -96])
            ->tag('monolog.logger', ['channel' => 'request'])

        ->set('api_platform.action.placeholder', PlaceholderAction::class)
            ->public()
        ->alias('api_platform.action.get_collection', 'api_platform.action.placeholder')
            ->public()
        ->alias('api_platform.action.post_collection', 'api_platform.action.placeholder')
            ->public()
        ->alias('api_platform.action.get_item', 'api_platform.action.placeholder')
            ->public()
        ->alias('api_platform.action.patch_item', 'api_platform.action.placeholder')
            ->public()
        ->alias('api_platform.action.put_item', 'api_platform.action.placeholder')
            ->public()
        ->alias('api_platform.action.delete_item', 'api_platform.action.placeholder')
            ->public()
        ->alias('api_platform.action.get_subresource', 'api_platform.action.placeholder')
            ->public()

        ->set('api_platform.action.not_found', NotFoundAction::class)
            ->public()
        ->alias(NotFoundAction::class, 'api_platform.action.not_found')
            ->public()

        ->set('api_platform.action.entrypoint', EntrypointAction::class)
            ->args([service('api_platform.metadata.resource.name_collection_factory')])
            ->public()

        ->set('api_platform.action.documentation', DocumentationAction::class)
            ->args([service('api_platform.metadata.resource.name_collection_factory'), '%api_platform.title%', '%api_platform.description%', '%api_platform.version%', null, '%api_platform.swagger.versions%', service('api_platform.openapi.factory')])
            ->public()

        ->set('api_platform.action.exception', ExceptionAction::class)
            ->args([service('api_platform.serializer'), '%api_platform.error_formats%', '%api_platform.exception_to_status%'])
            ->public()

        ->set('api_platform.identifiers_extractor', IdentifiersExtractor::class)
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.property_accessor'), service('api_platform.resource_class_resolver')])

        ->set('api_platform.identifiers_extractor.cached', CachedIdentifiersExtractor::class)
            ->decorate('api_platform.identifiers_extractor')
            ->args([service('api_platform.cache.identifiers_extractor'), service('api_platform.identifiers_extractor.cached.inner'), service('api_platform.property_accessor'), service('api_platform.resource_class_resolver')])
        ->alias(IdentifiersExtractorInterface::class, 'api_platform.identifiers_extractor.cached')

        ->set('api_platform.identifier.converter', IdentifierConverter::class)
            ->args([service('api_platform.identifiers_extractor.cached'), service('api_platform.metadata.property.metadata_factory'), tagged_iterator('api_platform.identifier.denormalizer')])

        ->set('api_platform.identifier.integer', IntegerDenormalizer::class)
            ->tag('api_platform.identifier.denormalizer')

        ->set('api_platform.identifier.date_normalizer', DateTimeIdentifierDenormalizer::class)
            ->tag('api_platform.identifier.denormalizer')

        ->set('api_platform.subresource_operation_factory', SubresourceOperationFactory::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.path_segment_name_generator')])

        ->set('api_platform.subresource_operation_factory.cached', CachedSubresourceOperationFactory::class)
            ->decorate('api_platform.subresource_operation_factory', null, -10)
            ->args([service('api_platform.cache.subresource_operation_factory'), service('api_platform.subresource_operation_factory.cached.inner')])

        ->set('api_platform.cache.route_name_resolver')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('api_platform.cache.identifiers_extractor')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('api_platform.cache.subresource_operation_factory')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('api_platform.cache_warmer.cache_pool_clearer', CachePoolClearerCacheWarmer::class)
            ->args([service('cache.system_clearer'), ['api_platform.cache.metadata.property', 'api_platform.cache.metadata.resource', 'api_platform.cache.route_name_resolver', 'api_platform.cache.identifiers_extractor', 'api_platform.cache.subresource_operation_factory', 'api_platform.elasticsearch.cache.metadata.document']])
            ->tag('kernel.cache_warmer', ['priority' => 64]);
};
