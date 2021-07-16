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
 * Trait for filtering the collection by whether a property value exists or not.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait ExistsFilterTrait
{
    use PropertyHelperTrait;

    /**
     * @var string Keyword used to retrieve the value
     */
    private $existsParameterName;

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
            if (!$this->isPropertyMapped($property, $resourceClass, true) || !$this->isNullableField($property, $resourceClass)) {
                continue;
            }
            $propertyName = $this->normalizePropertyName($property);
            $description[sprintf('%s[%s]', $this->existsParameterName, $propertyName)] = [
                'property' => $propertyName,
                'type' => 'bool',
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * Determines whether the given property refers to a nullable field.
     */
    abstract protected function isNullableField(string $property, string $resourceClass): bool;

    abstract protected function getProperties(): ?array;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function normalizePropertyName($property);

    private function normalizeValue($value, string $property): ?bool
    {
        if (\is_array($value) && isset($value[self::QUERY_PARAMETER_KEY])) {
            @trigger_error(
                sprintf('The ExistsFilter syntax "%s[exists]=true/false" is deprecated since 2.5. Use the syntax "%s[%s]=true/false" instead.', $property, $this->existsParameterName, $property),
                \E_USER_DEPRECATED
            );
            $value = $value[self::QUERY_PARAMETER_KEY];
        }

        if (\in_array($value, [true, 'true', '1', '', null], true)) {
            return true;
        }

        if (\in_array($value, [false, 'false', '0'], true)) {
            return false;
        }

        $this->getLogger()->notice('Invalid filter ignored', [
            'exception' => new InvalidArgumentException(sprintf('Invalid value for "%s[%s]", expected one of ( "%s" )', $this->existsParameterName, $property, implode('" | "', [
                'true',
                'false',
                '1',
                '0',
            ]))),
        ]);

        return null;
    }
}
