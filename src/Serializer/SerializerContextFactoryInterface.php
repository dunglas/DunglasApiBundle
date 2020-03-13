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

namespace ApiPlatform\Core\Serializer;

/**
 * Creates the context used by the Symfony Serializer.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface SerializerContextFactoryInterface
{
    /**
     * Creates a serialization context.
     */
    public function create(string $resourceClass, string $operationName, bool $normalization, array $context): array;
}
