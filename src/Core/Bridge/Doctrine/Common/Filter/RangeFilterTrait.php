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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Trait for filtering the collection by range.
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait RangeFilterTrait
{
    use PropertyHelperTrait;

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }

            $description += $this->getFilterDescription($property, self::PARAMETER_BETWEEN);
            $description += $this->getFilterDescription($property, self::PARAMETER_GREATER_THAN);
            $description += $this->getFilterDescription($property, self::PARAMETER_GREATER_THAN_OR_EQUAL);
            $description += $this->getFilterDescription($property, self::PARAMETER_LESS_THAN);
            $description += $this->getFilterDescription($property, self::PARAMETER_LESS_THAN_OR_EQUAL);
        }

        return $description;
    }

    abstract protected function getProperties(): ?array;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function normalizePropertyName($property);

    /**
     * Gets filter description.
     */
    protected function getFilterDescription(string $fieldName, string $operator): array
    {
        $propertyName = $this->normalizePropertyName($fieldName);

        return [
            sprintf('%s[%s]', $propertyName, $operator) => [
                'property' => $propertyName,
                'type' => 'string',
                'required' => false,
            ],
        ];
    }

    private function normalizeValues(array $values, string $property): ?array
    {
        $operators = [self::PARAMETER_BETWEEN, self::PARAMETER_GREATER_THAN, self::PARAMETER_GREATER_THAN_OR_EQUAL, self::PARAMETER_LESS_THAN, self::PARAMETER_LESS_THAN_OR_EQUAL];

        foreach ($values as $operator => $value) {
            if (!\in_array($operator, $operators, true)) {
                unset($values[$operator]);
            }
        }

        if (empty($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('At least one valid operator ("%s") is required for "%s" property', implode('", "', $operators), $property)),
            ]);

            return null;
        }

        return $values;
    }

    /**
     * Normalize the values array for between operator.
     */
    private function normalizeBetweenValues(array $values): ?array
    {
        if (2 !== \count($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid format for "[%s]", expected "<min>..<max>"', self::PARAMETER_BETWEEN)),
            ]);

            return null;
        }

        if (!is_numeric($values[0]) || !is_numeric($values[1])) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid values for "[%s]" range, expected numbers', self::PARAMETER_BETWEEN)),
            ]);

            return null;
        }

        return [$values[0] + 0, $values[1] + 0]; // coerce to the right types.
    }

    /**
     * Normalize the value.
     *
     * @return int|float|null
     */
    private function normalizeValue(string $value, string $operator)
    {
        if (!is_numeric($value)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid value for "[%s]", expected number', $operator)),
            ]);

            return null;
        }

        return $value + 0; // coerce $value to the right type.
    }
}
