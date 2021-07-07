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

use ApiPlatform\Core\Operation\DashPathSegmentNameGenerator;

/**
 * Generates a path with words separated by underscores.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 *
 * @deprecated since version 2.1, to be removed in 3.0. Use {@see \ApiPlatform\Core\Operation\DashPathSegmentNameGenerator} instead.
 */
final class DashOperationPathResolver implements OperationPathResolverInterface
{
    public function __construct()
    {
        @trigger_error(sprintf('The use of %s is deprecated since 2.1. Please use %s instead.', __CLASS__, DashPathSegmentNameGenerator::class), \E_USER_DEPRECATED);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType/*, string $operationName = null*/): string
    {
        if (\func_num_args() >= 4) {
            $operationName = func_get_arg(3);
        } else {
            $operationName = null;
        }

        return (new OperationPathResolver(new DashPathSegmentNameGenerator()))->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);
    }
}
