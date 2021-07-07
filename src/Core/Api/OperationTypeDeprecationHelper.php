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

namespace ApiPlatform\Core\Api;

/**
 * Class OperationTypeDeprecationHelper
 * Before API Platform 2.1, the operation type was one of:
 * - "collection" (true)
 * - "item" (false).
 *
 * Because we introduced a third type in API Platform 2.1, we're using a string with OperationType constants:
 * - OperationType::ITEM
 * - OperationType::COLLECTION
 * - OperationType::SUBRESOURCE
 *
 * @internal
 */
final class OperationTypeDeprecationHelper
{
    /**
     * @param string|bool $operationType
     */
    public static function getOperationType($operationType): string
    {
        if (\is_bool($operationType)) {
            @trigger_error('Using a boolean for the Operation Type is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3', \E_USER_DEPRECATED);

            $operationType = $operationType ? OperationType::COLLECTION : OperationType::ITEM;
        }

        return $operationType;
    }
}
