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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Util\FieldDatatypeTrait;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Applies selected sorting while querying resource collection.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class SortExtension implements RequestBodySearchCollectionExtensionInterface
{
    use FieldDatatypeTrait;

    private $defaultDirection;
    private $identifierExtractor;
    private $resourceMetadataFactory;
    private $nameConverter;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, IdentifierExtractorInterface $identifierExtractor, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, ?NameConverterInterface $nameConverter = null, ?string $defaultDirection = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->identifierExtractor = $identifierExtractor;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->nameConverter = $nameConverter;
        $this->defaultDirection = $defaultDirection;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(array $requestBody, string $resourceClass, ?string $operationName = null, array $context = []): array
    {
        $orders = [];

        if (
            null !== ($defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order'))
            && \is_array($defaultOrder)
        ) {
            foreach ($defaultOrder as $property => $direction) {
                if (\is_int($property)) {
                    $property = $direction;
                    $direction = 'asc';
                }

                $orders[] = $this->getOrder($resourceClass, $property, $direction);
            }
        } elseif (null !== $this->defaultDirection) {
            $orders[] = $this->getOrder(
                $resourceClass,
                $this->identifierExtractor->getIdentifierFromResourceClass($resourceClass),
                $this->defaultDirection
            );
        }

        if (!$orders) {
            return $requestBody;
        }

        $requestBody['sort'] = array_merge_recursive($requestBody['sort'] ?? [], $orders);

        return $requestBody;
    }

    private function getOrder(string $resourceClass, string $property, string $direction): array
    {
        $order = ['order' => strtolower($direction)];

        if (null !== $nestedPath = $this->getNestedFieldPath($resourceClass, $property)) {
            $nestedPath = null === $this->nameConverter ? $nestedPath : $this->nameConverter->normalize($nestedPath, $resourceClass);
            $order['nested'] = ['path' => $nestedPath];
        }

        $property = null === $this->nameConverter ? $property : $this->nameConverter->normalize($property, $resourceClass);

        return [$property => $order];
    }
}
