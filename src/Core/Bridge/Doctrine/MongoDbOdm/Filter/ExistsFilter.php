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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filters the collection by whether a property value exists or not.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not one of ( "true" | "false" | "1" | "0" ) the property is ignored.
 *
 * A query parameter with key but no value is treated as `true`, e.g.:
 * Request: GET /products?exists[brand]
 * Interpretation: filter products which have a brand
 *
 * @experimental
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ExistsFilter extends AbstractFilter implements ExistsFilterInterface
{
    use ExistsFilterTrait;

    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, array $properties = null, string $existsParameterName = self::QUERY_PARAMETER_KEY, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->existsParameterName = $existsParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (!\is_array($context['filters'][$this->existsParameterName] ?? null)) {
            $context['exists_deprecated_syntax'] = true;
            parent::apply($aggregationBuilder, $resourceClass, $operationName, $context);

            return;
        }

        foreach ($context['filters'][$this->existsParameterName] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $aggregationBuilder, $resourceClass, $operationName, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (
            (($context['exists_deprecated_syntax'] ?? false) && !isset($value[self::QUERY_PARAMETER_KEY])) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true) ||
            !$this->isNullableField($property, $resourceClass)
        ) {
            return;
        }

        $value = $this->normalizeValue($value, $property);
        if (null === $value) {
            return;
        }

        $matchField = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        $aggregationBuilder->match()->field($matchField)->{$value ? 'notEqual' : 'equals'}(null);
    }

    /**
     * {@inheritdoc}
     */
    protected function isNullableField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        $field = $propertyParts['field'];

        return $metadata instanceof ClassMetadata && $metadata->hasField($field) ? $metadata->isNullable($field) : false;
    }
}
