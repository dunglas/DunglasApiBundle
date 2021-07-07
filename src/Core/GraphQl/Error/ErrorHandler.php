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

/**
 * Handles the errors thrown by the GraphQL library by applying the formatter to them (default behavior).
 *
 * @experimental
 *
 * @author Ollie Harridge <code@oll.ie>
 */
final class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(array $errors, callable $formatter): array
    {
        return array_map($formatter, $errors);
    }
}
