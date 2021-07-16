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

namespace ApiPlatform\Core\PathResolver;

/**
 * Resolves the path of a resource operation.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 */
interface OperationPathResolverInterface
{
    /**
     * Resolves the operation path.
     *
     * @param string      $resourceShortName When the operation type is a subresource and the operation has more than one identifier, this value is the previous operation path
     * @param array       $operation         The operation metadata
     * @param string|bool $operationType     One of the constants defined in ApiPlatform\Core\Api\OperationType
     *                                       If the property is a boolean, true represents OperationType::COLLECTION, false is for OperationType::ITEM
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType/*, string $operationName = null*/): string;
}
