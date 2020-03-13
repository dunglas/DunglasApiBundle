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

namespace ApiPlatform\Core\Serializer\Filter;

use ApiPlatform\Core\Api\FilterInterface as BaseFilterInterface;

/**
 * Symfony serializer context filter interface.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface SerializerContextFilterInterface extends BaseFilterInterface
{
    /**
     * Apply a filter to the serializer context.
     */
    public function applyToSerializerContext(string $resourceClass, string $operationName, bool $normalization, array $context, array &$serializerContext): void;
}
