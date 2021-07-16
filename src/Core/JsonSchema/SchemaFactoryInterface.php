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

/**
 * Factory for creating the JSON Schema document corresponding to a PHP class.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface SchemaFactoryInterface
{
    /**
     * Builds the JSON Schema document corresponding to the given PHP class.
     */
    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema;
}
