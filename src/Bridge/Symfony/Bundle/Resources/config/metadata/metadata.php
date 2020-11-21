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

use ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property\PropertyInfoPropertyMetadataFactory;
use ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\Factory\DefaultPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\SerializerPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\CachedResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\CachedResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\FormatsResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\InputOutputResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\OperationResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ShortNameResourceMetadataFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.metadata.resource.name_collection_factory.cached', CachedResourceNameCollectionFactory::class)
            ->decorate('api_platform.metadata.resource.name_collection_factory', null, -10)
            ->args([service('api_platform.cache.metadata.resource'), service('api_platform.metadata.resource.name_collection_factory.cached.inner')])

        ->alias(ResourceNameCollectionFactoryInterface::class, 'api_platform.metadata.resource.name_collection_factory')

        ->set('api_platform.metadata.resource.metadata_factory.input_output', InputOutputResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 30)
            ->args([service('api_platform.metadata.resource.metadata_factory.input_output.inner')])

        ->set('api_platform.metadata.resource.metadata_factory.short_name', ShortNameResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 20)
            ->args([service('api_platform.metadata.resource.metadata_factory.short_name.inner')])

        ->set('api_platform.metadata.resource.metadata_factory.operation', OperationResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 10)
            ->args([service('api_platform.metadata.resource.metadata_factory.operation.inner'), '%api_platform.patch_formats%'])

        ->set('api_platform.metadata.resource.metadata_factory.formats', FormatsResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 5)
            ->args([service('api_platform.metadata.resource.metadata_factory.formats.inner'), '%api_platform.formats%', '%api_platform.patch_formats%'])

        ->set('api_platform.metadata.resource.metadata_factory.cached', CachedResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, -10)
            ->args([service('api_platform.cache.metadata.resource'), service('api_platform.metadata.resource.metadata_factory.cached.inner')])
        ->alias(ResourceMetadataFactoryInterface::class, 'api_platform.metadata.resource.metadata_factory')

        ->alias('api_platform.metadata.property.name_collection_factory', 'api_platform.metadata.property.name_collection_factory.property_info')
        ->alias(PropertyNameCollectionFactoryInterface::class, 'api_platform.metadata.property.name_collection_factory')

        ->set('api_platform.metadata.property.name_collection_factory.property_info', PropertyInfoPropertyNameCollectionFactory::class)
            ->args([service('api_platform.property_info')])

        ->set('api_platform.metadata.property.name_collection_factory.cached', CachedPropertyNameCollectionFactory::class)
            ->decorate('api_platform.metadata.property.name_collection_factory', null, -10)
            ->args([service('api_platform.cache.metadata.property'), service('api_platform.metadata.property.name_collection_factory.cached.inner')])

        ->set('api_platform.metadata.property.metadata_factory.property_info', PropertyInfoPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
            ->args([service('api_platform.property_info'), service('api_platform.metadata.property.metadata_factory.property_info.inner')])

        ->set('api_platform.metadata.property.metadata_factory.serializer', SerializerPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('serializer.mapping.class_metadata_factory'), service('api_platform.metadata.property.metadata_factory.serializer.inner'), service('api_platform.resource_class_resolver')])

        ->set('api_platform.metadata.property.metadata_factory.cached', CachedPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, -10)
            ->args([service('api_platform.cache.metadata.property'), service('api_platform.metadata.property.metadata_factory.cached.inner')])

        ->set('api_platform.metadata.property.metadata_factory.default_property', DefaultPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
            ->args([service('api_platform.metadata.property.metadata_factory.default_property.inner')])

        ->alias(PropertyMetadataFactoryInterface::class, 'api_platform.metadata.property.metadata_factory')

        ->set('api_platform.cache.metadata.resource')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('api_platform.cache.metadata.property')
            ->parent('cache.system')
            ->tag('cache.pool');
};
