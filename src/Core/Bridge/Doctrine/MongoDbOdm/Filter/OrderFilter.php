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

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\OrderFilterTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Order the collection by given properties.
 *
 * The ordering is done in the same sequence as they are specified in the query,
 * and for each property a direction value can be specified.
 *
 * For each property passed, if the resource does not have such property or if the
 * direction value is different from "asc" or "desc" (case insensitive), the property
 * is ignored.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class OrderFilter extends AbstractFilter implements OrderFilterInterface
{
    use OrderFilterTrait;

    public function __construct(ManagerRegistry $managerRegistry, string $orderParameterName = 'order', LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        if (null !== $properties) {
            $properties = array_map(static function ($propertyOptions) {
                // shorthand for default direction
                if (\is_string($propertyOptions)) {
                    $propertyOptions = [
                        'default_direction' => $propertyOptions,
                    ];
                }

                return $propertyOptions;
            }, $properties);
        }

        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->orderParameterName = $orderParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (isset($context['filters']) && !isset($context['filters'][$this->orderParameterName])) {
            return;
        }

        if (!isset($context['filters'][$this->orderParameterName]) || !\is_array($context['filters'][$this->orderParameterName])) {
            parent::apply($aggregationBuilder, $resourceClass, $operationName, $context);

            return;
        }

        foreach ($context['filters'][$this->orderParameterName] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $aggregationBuilder, $resourceClass, $operationName, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $direction, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (!$this->isPropertyEnabled($property, $resourceClass) || !$this->isPropertyMapped($property, $resourceClass)) {
            return;
        }

        $direction = $this->normalizeValue($direction, $property);
        if (null === $direction) {
            return;
        }

        $matchField = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        $aggregationBuilder->sort(
            $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$matchField => $direction]
        );
    }
}
