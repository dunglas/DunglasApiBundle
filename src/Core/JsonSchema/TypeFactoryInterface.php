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

namespace ApiPlatform\Core\JsonSchema;

use Symfony\Component\PropertyInfo\Type;

/**
 * Factory for creating the JSON Schema document which specifies the data type corresponding to a PHP type.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface TypeFactoryInterface
{
    /**
     * Gets the JSON Schema document which specifies the data type corresponding to the given PHP type, and recursively adds needed new schema to the current schema if provided.
     */
    public function getType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, Schema $schema = null): array;
}
