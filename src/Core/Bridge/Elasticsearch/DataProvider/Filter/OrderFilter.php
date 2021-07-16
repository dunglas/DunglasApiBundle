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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Order the collection by given properties.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class OrderFilter extends AbstractFilter implements SortFilterInterface
{
    private $orderParameterName;

    /**
     * {@inheritdoc}
     */
    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, ?NameConverterInterface $nameConverter = null, string $orderParameterName = 'order', ?array $properties = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $resourceClassResolver, $nameConverter, $properties);

        $this->orderParameterName = $orderParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(array $clauseBody, string $resourceClass, ?string $operationName = null, array $context = []): array
    {
        if (!\is_array($properties = $context['filters'][$this->orderParameterName] ?? [])) {
            return $clauseBody;
        }

        $orders = [];

        foreach ($properties as $property => $direction) {
            [$type] = $this->getMetadata($resourceClass, $property);

            if (!$type) {
                continue;
            }

            if (empty($direction) && null !== $defaultDirection = $this->properties[$property] ?? null) {
                $direction = $defaultDirection;
            }

            if (!\in_array($direction = strtolower($direction), ['asc', 'desc'], true)) {
                continue;
            }

            $order = ['order' => $direction];

            if (null !== $nestedPath = $this->getNestedFieldPath($resourceClass, $property)) {
                $nestedPath = null === $this->nameConverter ? $nestedPath : $this->nameConverter->normalize($nestedPath, $resourceClass, null, $context);
                $order['nested'] = ['path' => $nestedPath];
            }

            $property = null === $this->nameConverter ? $property : $this->nameConverter->normalize($property, $resourceClass, null, $context);
            $orders[] = [$property => $order];
        }

        if (!$orders) {
            return $clauseBody;
        }

        return array_merge_recursive($clauseBody, $orders);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->getProperties($resourceClass) as $property) {
            [$type] = $this->getMetadata($resourceClass, $property);

            if (!$type) {
                continue;
            }

            $description["$this->orderParameterName[$property]"] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
            ];
        }

        return $description;
    }
}
