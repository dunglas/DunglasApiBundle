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

namespace ApiPlatform\Core\Filter;

use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Exception\FilterValidationException;
use Psr\Container\ContainerInterface;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
class QueryParameterValidator
{
    use FilterLocatorTrait;

    private $validators;

    public function __construct(ContainerInterface $filterLocator)
    {
        $this->setFilterLocator($filterLocator);

        $this->validators = [
            new Validator\ArrayItems(),
            new Validator\Bounds(),
            new Validator\Enum(),
            new Validator\Length(),
            new Validator\MultipleOf(),
            new Validator\Pattern(),
            new Validator\Required(),
        ];
    }

    public function validateFilters(string $resourceClass, array $resourceFilters, array $queryParameters): void
    {
        $errorList = [];

        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($resourceClass) as $name => $data) {
                foreach ($this->validators as $validator) {
                    $errorList = array_merge($errorList, $validator->validate($name, $data, $queryParameters));
                }
            }
        }

        if ($errorList) {
            throw new FilterValidationException($errorList);
        }
    }
}
