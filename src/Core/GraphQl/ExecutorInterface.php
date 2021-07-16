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

namespace ApiPlatform\Core\GraphQl;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;

/**
 * Wrapper for the GraphQL facade.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface ExecutorInterface
{
    /**
     * @see http://webonyx.github.io/graphql-php/executing-queries/#using-facade-method
     *
     * @param mixed|null $rootValue
     * @param mixed|null $context
     */
    public function executeQuery(Schema $schema, $source, $rootValue = null, $context = null, array $variableValues = null, string $operationName = null, callable $fieldResolver = null, array $validationRules = null): ExecutionResult;
}
