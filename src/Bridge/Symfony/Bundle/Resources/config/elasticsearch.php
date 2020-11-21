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

use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractor;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\ConstantScoreFilterExtension;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortExtension;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortFilterExtension;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\ItemDataProvider;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\CachedDocumentMetadataFactory;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\CatDocumentMetadataFactory;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\ConfiguredDocumentMetadataFactory;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Resource\Factory\ElasticsearchOperationResourceMetadataFactory;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\ItemNormalizer;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter;
use Elasticsearch\Client;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.elasticsearch.client', Client::class)

        ->set('api_platform.elasticsearch.metadata.resource.metadata_factory.operation', ElasticsearchOperationResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 10)
            ->args([service('api_platform.elasticsearch.metadata.resource.metadata_factory.operation.inner')])

        ->set('api_platform.elasticsearch.cache.metadata.document')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('api_platform.elasticsearch.metadata.document.metadata_factory.configured', ConfiguredDocumentMetadataFactory::class)
            ->args(['%api_platform.elasticsearch.mapping%'])
        ->alias('api_platform.elasticsearch.metadata.document.metadata_factory', 'api_platform.elasticsearch.metadata.document.metadata_factory.configured')
        ->alias(DocumentMetadataFactoryInterface::class, 'api_platform.elasticsearch.metadata.document.metadata_factory')

        ->set('api_platform.elasticsearch.metadata.document.metadata_factory.attribute', AttributeDocumentMetadataFactory::class)
            ->decorate('api_platform.elasticsearch.metadata.document.metadata_factory', null, 20)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.elasticsearch.metadata.document.metadata_factory.attribute.inner')])

        ->set('api_platform.elasticsearch.metadata.document.metadata_factory.cat', CatDocumentMetadataFactory::class)
            ->decorate('api_platform.elasticsearch.metadata.document.metadata_factory', null, 10)
            ->args([service('api_platform.elasticsearch.client'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.elasticsearch.metadata.document.metadata_factory.cat.inner')])

        ->set('api_platform.elasticsearch.metadata.document.metadata_factory.cached', CachedDocumentMetadataFactory::class)
            ->decorate('api_platform.elasticsearch.metadata.document.metadata_factory', null, -10)
            ->args([service('api_platform.elasticsearch.cache.metadata.document'), service('api_platform.elasticsearch.metadata.document.metadata_factory.cached.inner')])

        ->set('api_platform.elasticsearch.identifier_extractor', IdentifierExtractor::class)
            ->args([service('api_platform.identifiers_extractor')])
        ->alias(IdentifierExtractorInterface::class, 'api_platform.elasticsearch.identifier_extractor')

        ->set('api_platform.elasticsearch.name_converter.inner_fields', InnerFieldsNameConverter::class)
            ->args([service('api_platform.name_converter')->ignoreOnInvalid()])

        ->set('api_platform.elasticsearch.normalizer.item', ItemNormalizer::class)
            ->args([service('api_platform.elasticsearch.identifier_extractor'), service('serializer.mapping.class_metadata_factory'), service('api_platform.elasticsearch.name_converter.inner_fields'), service('serializer.property_accessor'), service('property_info')->ignoreOnInvalid(), service('serializer.mapping.class_discriminator_resolver')->ignoreOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -890])

        ->set('api_platform.elasticsearch.item_data_provider', ItemDataProvider::class)
            ->args([service('api_platform.elasticsearch.client'), service('api_platform.elasticsearch.metadata.document.metadata_factory'), service('api_platform.elasticsearch.identifier_extractor'), service('serializer'), service('api_platform.metadata.resource.metadata_factory')])
            ->tag('api_platform.item_data_provider', ['priority' => 5])

        ->set('api_platform.elasticsearch.collection_data_provider', CollectionDataProvider::class)
            ->args([service('api_platform.elasticsearch.client'), service('api_platform.elasticsearch.metadata.document.metadata_factory'), service('api_platform.elasticsearch.identifier_extractor'), service('serializer'), service('api_platform.pagination'), service('api_platform.metadata.resource.metadata_factory'), tagged_iterator('api_platform.elasticsearch.request_body_search_extension.collection')])
            ->tag('api_platform.collection_data_provider', ['priority' => 5])

        ->set('api_platform.elasticsearch.request_body_search_extension.filter')
            ->abstract()
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.filter_locator')])

        ->set('api_platform.elasticsearch.request_body_search_extension.constant_score_filter', ConstantScoreFilterExtension::class)
            ->parent('api_platform.elasticsearch.request_body_search_extension.filter')
            ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 30])

        ->set('api_platform.elasticsearch.request_body_search_extension.sort_filter', SortFilterExtension::class)
            ->parent('api_platform.elasticsearch.request_body_search_extension.filter')
            ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 20])

        ->set('api_platform.elasticsearch.request_body_search_extension.sort', SortExtension::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.elasticsearch.identifier_extractor'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.resource_class_resolver'), service('api_platform.elasticsearch.name_converter.inner_fields'), '%api_platform.collection.order%'])
            ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 10])

        ->set('api_platform.elasticsearch.search_filter')
            ->abstract()
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.resource_class_resolver'), service('api_platform.elasticsearch.identifier_extractor'), service('api_platform.iri_converter'), service('api_platform.property_accessor'), service('api_platform.elasticsearch.name_converter.inner_fields')])

        ->set('api_platform.elasticsearch.term_filter', TermFilter::class)
            ->abstract()
            ->parent('api_platform.elasticsearch.search_filter')
        ->alias(TermFilter::class, 'api_platform.elasticsearch.term_filter')

        ->set('api_platform.elasticsearch.match_filter', MatchFilter::class)
            ->abstract()
            ->parent('api_platform.elasticsearch.search_filter')
        ->alias(MatchFilter::class, 'api_platform.elasticsearch.match_filter')

        ->set('api_platform.elasticsearch.order_filter', OrderFilter::class)
            ->abstract()
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.resource_class_resolver'), service('api_platform.elasticsearch.name_converter.inner_fields'), '%api_platform.collection.order_parameter_name%'])
        ->alias(OrderFilter::class, 'api_platform.elasticsearch.order_filter');
};
