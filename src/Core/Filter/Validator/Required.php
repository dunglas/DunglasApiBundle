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

use ApiPlatform\Core\Util\RequestParser;

final class Required implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $name, array $filterDescription, array $queryParameters): array
    {
        // filter is not required, the `checkRequired` method can not break
        if (!($filterDescription['required'] ?? false)) {
            return [];
        }

        // if query param is not given, then break
        if (!$this->requestHasQueryParameter($queryParameters, $name)) {
            return [
                sprintf('Query parameter "%s" is required', $name),
            ];
        }

        // if query param is empty and the configuration does not allow it
        if (!($filterDescription['swagger']['allowEmptyValue'] ?? false) && empty($this->requestGetQueryParameter($queryParameters, $name))) {
            return [
                sprintf('Query parameter "%s" does not allow empty value', $name),
            ];
        }

        return [];
    }

    /**
     * Test if request has required parameter.
     */
    private function requestHasQueryParameter(array $queryParameters, string $name): bool
    {
        $matches = RequestParser::parseRequestParams($name);
        if (!$matches) {
            return false;
        }

        $rootName = array_keys($matches)[0] ?? '';
        if (!$rootName) {
            return false;
        }

        if (\is_array($matches[$rootName])) {
            $keyName = array_keys($matches[$rootName])[0];

            $queryParameter = $queryParameters[(string) $rootName] ?? null;

            return \is_array($queryParameter) && isset($queryParameter[$keyName]);
        }

        return \array_key_exists((string) $rootName, $queryParameters);
    }

    /**
     * Test if required filter is valid. It validates array notation too like "required[bar]".
     *
     * @return ?mixed
     */
    private function requestGetQueryParameter(array $queryParameters, string $name)
    {
        $matches = RequestParser::parseRequestParams($name);
        if (empty($matches)) {
            return null;
        }

        $rootName = array_keys($matches)[0] ?? '';
        if (!$rootName) {
            return null;
        }

        if (\is_array($matches[$rootName])) {
            $keyName = array_keys($matches[$rootName])[0];

            $queryParameter = $queryParameters[(string) $rootName] ?? null;

            if (\is_array($queryParameter) && isset($queryParameter[$keyName])) {
                return $queryParameter[$keyName];
            }

            return null;
        }

        return $queryParameters[(string) $rootName];
    }
}
