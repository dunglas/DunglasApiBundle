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

use ApiPlatform\Core\Hal\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Hal\Serializer\EntrypointNormalizer;
use ApiPlatform\Core\Hal\Serializer\ItemNormalizer;
use ApiPlatform\Core\Hal\Serializer\ObjectNormalizer;
use ApiPlatform\Core\Serializer\JsonEncoder;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.hal.encoder', JsonEncoder::class)
            ->args(['jsonhal'])
            ->tag('serializer.encoder')

        ->set('api_platform.hal.normalizer.entrypoint', EntrypointNormalizer::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.iri_converter'), service('api_platform.router')])
            ->tag('serializer.normalizer', ['priority' => -800])

        ->set('api_platform.hal.normalizer.collection', CollectionNormalizer::class)
            ->args([service('api_platform.resource_class_resolver'), '%api_platform.collection.pagination.page_parameter_name%', service('api_platform.metadata.resource.metadata_factory')])
            ->tag('serializer.normalizer', ['priority' => -985])

        ->set('api_platform.hal.normalizer.item', ItemNormalizer::class)
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.iri_converter'), service('api_platform.resource_class_resolver'), service('api_platform.property_accessor'), service('api_platform.name_converter')->ignoreOnInvalid(), service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(), null, false, [], tagged_iterator('api_platform.data_transformer'), service('api_platform.metadata.resource.metadata_factory')->ignoreOnInvalid(), service('api_platform.security.resource_access_checker')->ignoreOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -890])

        ->set('api_platform.hal.normalizer.object', ObjectNormalizer::class)
            ->args([service('serializer.normalizer.object'), service('api_platform.iri_converter')])
            ->tag('serializer.normalizer', ['priority' => -995]);
};
