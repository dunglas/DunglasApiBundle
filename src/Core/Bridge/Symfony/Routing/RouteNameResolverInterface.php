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

use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Resolves the Symfony route name associated with a resource.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
interface RouteNameResolverInterface
{
    /**
     * Finds the route name for a resource.
     *
     * @param bool|string $operationType
     *
     * @throws InvalidArgumentException
     */
    public function getRouteName(string $resourceClass, $operationType /*, array $context = [] */): string;
}
