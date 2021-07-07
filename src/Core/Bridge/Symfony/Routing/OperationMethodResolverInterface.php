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

use ApiPlatform\Core\Api\OperationMethodResolverInterface as BaseOperationMethodResolverInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\Routing\Route;

/**
 * Resolves the HTTP method associated with an operation, extended for Symfony routing.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @deprecated since API Platform 2.5, use the "method" attribute instead
 */
interface OperationMethodResolverInterface extends BaseOperationMethodResolverInterface
{
    /**
     * @throws RuntimeException
     */
    public function getCollectionOperationRoute(string $resourceClass, string $operationName): Route;

    /**
     * @throws RuntimeException
     */
    public function getItemOperationRoute(string $resourceClass, string $operationName): Route;
}
