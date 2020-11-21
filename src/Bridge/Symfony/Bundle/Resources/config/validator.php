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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory;
use ApiPlatform\Core\Bridge\Symfony\Validator\Validator;
use ApiPlatform\Core\EventListener\QueryParameterValidateListener;
use ApiPlatform\Core\Filter\QueryParameterValidator;
use ApiPlatform\Core\Validator\EventListener\ValidateListener;
use ApiPlatform\Core\Validator\ValidatorInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.validator', Validator::class)
            ->args([service('validator'), service('service_container')])
        ->alias(ValidatorInterface::class, 'api_platform.validator')

        ->set('api_platform.metadata.property.metadata_factory.validator', ValidatorPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 20)
            ->args([service('validator'), service('api_platform.metadata.property.metadata_factory.validator.inner'), tagged_iterator('api_platform.metadata.property_schema_restriction')])

        ->set('api_platform.metadata.property_schema.length_restriction', PropertySchemaLengthRestriction::class)
            ->tag('api_platform.metadata.property_schema_restriction')

        ->set('api_platform.metadata.property_schema.regex_restriction', PropertySchemaRegexRestriction::class)
            ->tag('api_platform.metadata.property_schema_restriction')

        ->set('api_platform.metadata.property_schema.format_restriction', PropertySchemaFormat::class)
            ->tag('api_platform.metadata.property_schema_restriction')

        ->set('api_platform.listener.view.validate', ValidateListener::class)
            ->args([service('api_platform.validator'), service('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 64])

        ->set('api_platform.validator.query_parameter_validator', QueryParameterValidator::class)
            ->args([service('api_platform.filter_locator')])

        ->set('api_platform.listener.view.validate_query_parameters', QueryParameterValidateListener::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.validator.query_parameter_validator')])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 16]);
};
