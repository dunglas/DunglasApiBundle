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

namespace ApiPlatform\Core\Action;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An action which always returns HTTP 404 Not Found. Useful for disabling an operation.
 */
final class NotFoundAction
{
    public function __invoke()
    {
        throw new NotFoundHttpException();
    }
}
