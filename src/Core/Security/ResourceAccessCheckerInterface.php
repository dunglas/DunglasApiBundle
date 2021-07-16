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

namespace ApiPlatform\Core\Security;

/**
 * Checks if the logged user has sufficient permissions to access the given resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceAccessCheckerInterface
{
    /**
     * Checks if the given item can be accessed by the current user.
     */
    public function isGranted(string $resourceClass, string $expression, array $extraVariables = []): bool;
}
