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

/**
 * Trait for filtering the collection by date intervals.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait DateFilterTrait
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

        foreach ($properties as $property => $nullManagement) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isDateField($property, $resourceClass)) {
                continue;
            }

            $description += $this->getFilterDescription($property, self::PARAMETER_BEFORE, $resourceClass);
            $description += $this->getFilterDescription($property, self::PARAMETER_STRICTLY_BEFORE, $resourceClass);
            $description += $this->getFilterDescription($property, self::PARAMETER_AFTER, $resourceClass);
            $description += $this->getFilterDescription($property, self::PARAMETER_STRICTLY_AFTER, $resourceClass);
        }

        return $description;
    }

    abstract protected function getProperties(): ?array;

    abstract protected function normalizePropertyName($property/*, ?string $resourceClass = null, array $context = []*/);

    /**
     * Determines whether the given property refers to a date field.
     */
    protected function isDateField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_DATE_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    /**
     * Gets filter description.
     */
    protected function getFilterDescription(string $property, string $period/*, ?string $resourceClass = null*/): array
    {
        if (\func_num_args() > 2) {
            $resourceClass = null === ($arg = func_get_arg(2)) ? $arg : (string) $arg;
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a third `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.7.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }

            $resourceClass = null;
        }

        $propertyName = $this->normalizePropertyName($property, $resourceClass);

        return [
            sprintf('%s[%s]', $propertyName, $period) => [
                'property' => $propertyName,
                'type' => \DateTimeInterface::class,
                'required' => false,
            ],
        ];
    }

    private function normalizeValue($value, string $operator): ?string
    {
        if (false === \is_string($value)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid value for "[%s]", expected string', $operator)),
            ]);

            return null;
        }

        return $value;
    }
}
