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
 * Trait for filtering the collection by numeric values.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait NumericFilterTrait
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
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isNumericField($property, $resourceClass)) {
                continue;
            }

            $propertyName = $this->normalizePropertyName($property);
            $filterParameterNames = [$propertyName, $propertyName.'[]'];
            foreach ($filterParameterNames as $filterParameterName) {
                $description[$filterParameterName] = [
                    'property' => $propertyName,
                    'type' => $this->getType((string) $this->getDoctrineFieldType($property, $resourceClass)),
                    'required' => false,
                    'is_collection' => '[]' === substr((string) $filterParameterName, -2),
                ];
            }
        }

        return $description;
    }

    /**
     * Gets the PHP type corresponding to this Doctrine type.
     */
    abstract protected function getType(string $doctrineType = null): string;

    abstract protected function getProperties(): ?array;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function normalizePropertyName($property);

    /**
     * Determines whether the given property refers to a numeric field.
     */
    protected function isNumericField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_NUMERIC_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    protected function normalizeValues($value, string $property): ?array
    {
        if (!is_numeric($value) && (!\is_array($value) || !$this->isNumericArray($value))) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid numeric value for "%s" property', $property)),
            ]);

            return null;
        }

        $values = (array) $value;

        foreach ($values as $key => $val) {
            if (!\is_int($key)) {
                unset($values[$key]);

                continue;
            }
            $values[$key] = $val + 0; // coerce $val to the right type.
        }

        if (empty($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('At least one value is required, multiple values should be in "%1$s[]=firstvalue&%1$s[]=secondvalue" format', $property)),
            ]);

            return null;
        }

        return array_values($values);
    }

    protected function isNumericArray(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}
