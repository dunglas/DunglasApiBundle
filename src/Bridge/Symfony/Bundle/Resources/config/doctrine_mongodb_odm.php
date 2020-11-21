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

use ApiPlatform\Core\Bridge\Doctrine\Common\DataPersister;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\FilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\OrderExtension;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\PaginationExtension;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\ItemDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Metadata\Property\DoctrineMongoDbOdmPropertyMetadataFactory;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyInfo\DoctrineExtractor;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\SubresourceDataProvider;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor', DoctrineExtractor::class)
            ->args([service('doctrine_mongodb.odm.default_document_manager')])
            ->tag('property_info.list_extractor', ['priority' => -1001])
            ->tag('property_info.type_extractor', ['priority' => -999])

        ->set('api_platform.doctrine.metadata_factory', ClassMetadataFactory::class)
            ->factory([service('doctrine_mongodb.odm.default_document_manager'), 'getMetadataFactory'])

        ->set('api_platform.doctrine_mongodb.odm.data_persister', DataPersister::class)
            ->args([service('doctrine_mongodb')])
            ->tag('api_platform.data_persister', ['priority' => -1000])

        ->set('api_platform.doctrine_mongodb.odm.collection_data_provider')
            ->abstract()
            ->args([service('doctrine_mongodb'), service('api_platform.metadata.resource.metadata_factory'), tagged_iterator('api_platform.doctrine_mongodb.odm.aggregation_extension.collection')])

        ->set('api_platform.doctrine_mongodb.odm.item_data_provider')
            ->abstract()
            ->args([service('doctrine_mongodb'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), tagged_iterator('api_platform.doctrine_mongodb.odm.aggregation_extension.item')])

        ->set('api_platform.doctrine_mongodb.odm.subresource_data_provider')
            ->abstract()
            ->args([service('doctrine_mongodb'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), tagged_iterator('api_platform.doctrine_mongodb.odm.aggregation_extension.collection'), tagged_iterator('api_platform.doctrine_mongodb.odm.aggregation_extension.item')])

        ->set('api_platform.doctrine_mongodb.odm.default.collection_data_provider', CollectionDataProvider::class)
            ->parent('api_platform.doctrine_mongodb.odm.collection_data_provider')
            ->tag('api_platform.collection_data_provider')

        ->set('api_platform.doctrine_mongodb.odm.default.item_data_provider', ItemDataProvider::class)
            ->parent('api_platform.doctrine_mongodb.odm.item_data_provider')
            ->tag('api_platform.item_data_provider')

        ->set('api_platform.doctrine_mongodb.odm.default.subresource_data_provider', SubresourceDataProvider::class)
            ->parent('api_platform.doctrine_mongodb.odm.subresource_data_provider')
            ->tag('api_platform.subresource_data_provider')

        ->set('api_platform.doctrine_mongodb.odm.search_filter', SearchFilter::class)
            ->abstract()
            ->args([service('doctrine_mongodb'), service('api_platform.iri_converter'), service('api_platform.identifiers_extractor.cached'), service('api_platform.property_accessor'), service('logger')->ignoreOnInvalid(), '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(SearchFilter::class, 'api_platform.doctrine_mongodb.odm.search_filter')

        ->set('api_platform.doctrine_mongodb.odm.boolean_filter', BooleanFilter::class)
            ->abstract()
            ->args([service('doctrine_mongodb'), service('logger')->ignoreOnInvalid(), '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(BooleanFilter::class, 'api_platform.doctrine_mongodb.odm.boolean_filter')

        ->set('api_platform.doctrine_mongodb.odm.date_filter', DateFilter::class)
            ->abstract()
            ->args([service('doctrine_mongodb'), service('logger')->ignoreOnInvalid(), '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(DateFilter::class, 'api_platform.doctrine_mongodb.odm.date_filter')

        ->set('api_platform.doctrine_mongodb.odm.exists_filter', ExistsFilter::class)
            ->abstract()
            ->args([service('doctrine_mongodb'), service('logger')->ignoreOnInvalid(), '$existsParameterName' => '%api_platform.collection.exists_parameter_name%', '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(ExistsFilter::class, 'api_platform.doctrine_mongodb.odm.exists_filter')

        ->set('api_platform.doctrine_mongodb.odm.numeric_filter', NumericFilter::class)
            ->abstract()
            ->args([service('doctrine_mongodb'), service('logger')->ignoreOnInvalid(), '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(NumericFilter::class, 'api_platform.doctrine_mongodb.odm.numeric_filter')

        ->set('api_platform.doctrine_mongodb.odm.order_filter', OrderFilter::class)
            ->abstract()
            ->args([service('doctrine_mongodb'), '%api_platform.collection.order_parameter_name%', service('logger')->ignoreOnInvalid(), '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(OrderFilter::class, 'api_platform.doctrine_mongodb.odm.order_filter')

        ->set('api_platform.doctrine_mongodb.odm.range_filter', RangeFilter::class)
            ->abstract()
            ->args([service('doctrine_mongodb'), service('logger')->ignoreOnInvalid(), '$nameConverter' => service('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(RangeFilter::class, 'api_platform.doctrine_mongodb.odm.range_filter')

        ->set('api_platform.doctrine_mongodb.odm.metadata.property.metadata_factory', DoctrineMongoDbOdmPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
            ->args([service('doctrine_mongodb'), service('api_platform.doctrine_mongodb.odm.metadata.property.metadata_factory.inner')])

        ->set('api_platform.doctrine_mongodb.odm.aggregation_extension.filter', FilterExtension::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.filter_locator')])
            ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection', ['priority' => 32])
        ->alias(FilterExtension::class, 'api_platform.doctrine_mongodb.odm.aggregation_extension.filter')

        ->set('api_platform.doctrine_mongodb.odm.aggregation_extension.pagination', PaginationExtension::class)
            ->args([service('doctrine_mongodb'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.pagination')])
            ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection')
        ->alias(PaginationExtension::class, 'api_platform.doctrine_mongodb.odm.aggregation_extension.pagination')

        ->set('api_platform.doctrine_mongodb.odm.aggregation_extension.order', OrderExtension::class)
            ->args(['%api_platform.collection.order%', service('api_platform.metadata.resource.metadata_factory'), service('doctrine_mongodb')])
            ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection', ['priority' => 16])
        ->alias(OrderExtension::class, 'api_platform.doctrine_mongodb.odm.aggregation_extension.order');
};
