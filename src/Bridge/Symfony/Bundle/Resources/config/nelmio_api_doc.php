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

use ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider\ApiPlatformProvider;
use ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.nelmio_api_doc.annotations_provider', ApiPlatformProvider::class)
            ->args([service('api_platform.metadata.resource.name_collection_factory'), service('api_platform.hydra.normalizer.documentation'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.filter_locator'), service('api_platform.operation_method_resolver')])
            ->tag('nelmio_api_doc.extractor.annotations_provider')

        ->set('api_platform.nelmio_api_doc.parser', ApiPlatformParser::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.name_converter')->ignoreOnInvalid()])
            ->tag('nelmio_api_doc.extractor.parser');
};
