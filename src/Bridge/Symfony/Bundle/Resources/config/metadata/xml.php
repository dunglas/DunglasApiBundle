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

use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.metadata.extractor.xml', XmlExtractor::class)
            ->args([[], service('service_container')])

        ->set('api_platform.metadata.resource.name_collection_factory.xml', ExtractorResourceNameCollectionFactory::class)
            ->args([service('api_platform.metadata.extractor.xml')])
        ->alias('api_platform.metadata.resource.name_collection_factory', 'api_platform.metadata.resource.name_collection_factory.xml')

        ->set('api_platform.metadata.resource.metadata_factory.xml', ExtractorResourceMetadataFactory::class)
            ->args([service('api_platform.metadata.extractor.xml')])
        ->alias('api_platform.metadata.resource.metadata_factory', 'api_platform.metadata.resource.metadata_factory.xml')

        ->set('api_platform.metadata.property.name_collection_factory.xml', ExtractorPropertyNameCollectionFactory::class)
            ->decorate('api_platform.metadata.property.name_collection_factory')
            ->args([service('api_platform.metadata.extractor.xml'), service('api_platform.metadata.property.name_collection_factory.xml.inner')])

        ->set('api_platform.metadata.property.metadata_factory.xml', ExtractorPropertyMetadataFactory::class)
            ->args([service('api_platform.metadata.extractor.xml')])
        ->alias('api_platform.metadata.property.metadata_factory', 'api_platform.metadata.property.metadata_factory.xml');
};
