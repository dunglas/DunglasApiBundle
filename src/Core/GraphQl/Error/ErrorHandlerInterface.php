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

namespace ApiPlatform\Core\GraphQl\Error;

use GraphQL\Error\Error;

/**
 * Handles the errors thrown by the GraphQL library.
 * It is responsible for applying the formatter to the errors and can be used for filtering or logging them.
 *
 * @experimental
 *
 * @author Ollie Harridge <code@oll.ie>
 */
interface ErrorHandlerInterface
{
    /**
     * @param Error[] $errors
     */
    public function __invoke(array $errors, callable $formatter): array;
}
