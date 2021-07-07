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

namespace ApiPlatform\Core\Filter\Validator;

final class Pattern implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $name, array $filterDescription, array $queryParameters): array
    {
        $value = $queryParameters[$name] ?? null;
        if (empty($value) && '0' !== $value || !\is_string($value)) {
            return [];
        }

        $pattern = $filterDescription['swagger']['pattern'] ?? null;

        if (null !== $pattern && !preg_match($pattern, $value)) {
            return [
                sprintf('Query parameter "%s" must match pattern %s', $name, $pattern),
            ];
        }

        return [];
    }
}
