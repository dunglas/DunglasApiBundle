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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\OperationTypeDeprecationHelper;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\Inflector;

/**
 * Generates the Symfony route name associated with an operation name and a resource short name.
 *
 * @internal
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class RouteNameGenerator
{
    public const ROUTE_NAME_PREFIX = 'api_';

    private function __construct()
    {
    }

    /**
     * Generates a Symfony route name.
     *
     * @param string|bool $operationType
     *
     * @throws InvalidArgumentException
     */
    public static function generate(string $operationName, string $resourceShortName, $operationType): string
    {
        if (OperationType::SUBRESOURCE === $operationType = OperationTypeDeprecationHelper::getOperationType($operationType)) {
            throw new InvalidArgumentException('Subresource operations are not supported by the RouteNameGenerator.');
        }

        return sprintf(
            '%s%s_%s_%s',
            static::ROUTE_NAME_PREFIX,
            self::inflector($resourceShortName),
            $operationName,
            $operationType
        );
    }

    /**
     * Transforms a given string to a tableized, pluralized string.
     *
     * @param string $name usually a ResourceMetadata shortname
     *
     * @return string A string that is a part of the route name
     */
    public static function inflector(string $name, bool $pluralize = true): string
    {
        $name = Inflector::tableize($name);

        return $pluralize ? Inflector::pluralize($name) : $name;
    }
}
