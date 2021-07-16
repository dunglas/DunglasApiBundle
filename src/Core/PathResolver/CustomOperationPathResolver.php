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

use ApiPlatform\Core\Api\OperationTypeDeprecationHelper;

/**
 * Resolves the custom operations path.
 *
 * @author Guilhem N. <egetick@gmail.com>
 */
final class CustomOperationPathResolver implements OperationPathResolverInterface
{
    private $deferred;

    public function __construct(OperationPathResolverInterface $deferred)
    {
        $this->deferred = $deferred;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType/*, string $operationName = null*/): string
    {
        if (\func_num_args() >= 4) {
            $operationName = func_get_arg(3);
        } else {
            @trigger_error(sprintf('Method %s() will have a 4th `string $operationName` argument in version 3.0. Not defining it is deprecated since 2.1.', __METHOD__), \E_USER_DEPRECATED);

            $operationName = null;
        }

        return $operation['path'] ?? $this->deferred->resolveOperationPath($resourceShortName, $operation, OperationTypeDeprecationHelper::getOperationType($operationType), $operationName);
    }
}
