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

spl_autoload_register(function ($className) {
    static $deprecatedClasses = [
        // TODO: we can not work with an alias with doctrine annotations
        // ApiPlatform\Core\Annotation\ApiProperty::class => ApiPlatform\Metadata\ApiProperty::class,
        ApiPlatform\Core\Api\UrlGeneratorInterface::class => ApiPlatform\Api\UrlGeneratorInterface::class,
    ];

    if (isset($deprecatedClasses[$className])) {
        class_alias($deprecatedClasses[$className], $className);
        trigger_deprecation('api-platform/core', '2.7', sprintf('The class %s is deprecated, use %s instead.', $className, $deprecatedClasses[$className]));

        return;
    }
});
