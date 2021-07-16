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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Abstract class with helpers for easing the implementation of a search filter like a term filter or a match filter.
 *
 * @experimental
 *
 * @internal
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractSearchFilter extends AbstractFilter implements ConstantScoreFilterInterface
{
    protected $identifierExtractor;
    protected $iriConverter;
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, IdentifierExtractorInterface $identifierExtractor, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor, ?NameConverterInterface $nameConverter = null, ?array $properties = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $resourceClassResolver, $nameConverter, $properties);

        $this->identifierExtractor = $identifierExtractor;
        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(array $clauseBody, string $resourceClass, ?string $operationName = null, array $context = []): array
    {
        $searches = [];

        foreach ($context['filters'] ?? [] as $property => $values) {
            [$type, $hasAssociation, $nestedResourceClass, $nestedProperty] = $this->getMetadata($resourceClass, $property);

            if (!$type || !$values = (array) $values) {
                continue;
            }

            if ($hasAssociation || $this->isIdentifier($nestedResourceClass, $nestedProperty)) {
                $values = array_map([$this, 'getIdentifierValue'], $values, array_fill(0, \count($values), $nestedProperty));
            }

            if (!$this->hasValidValues($values, $type)) {
                continue;
            }

            $property = null === $this->nameConverter ? $property : $this->nameConverter->normalize($property, $resourceClass, null, $context);
            $nestedPath = $this->getNestedFieldPath($resourceClass, $property);
            $nestedPath = null === $nestedPath || null === $this->nameConverter ? $nestedPath : $this->nameConverter->normalize($nestedPath, $resourceClass, null, $context);

            $searches[] = $this->getQuery($property, $values, $nestedPath);
        }

        if (!$searches) {
            return $clauseBody;
        }

        return array_merge_recursive($clauseBody, [
            'bool' => [
                'must' => $searches,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->getProperties($resourceClass) as $property) {
            [$type, $hasAssociation] = $this->getMetadata($resourceClass, $property);

            if (!$type) {
                continue;
            }

            foreach ([$property, "${property}[]"] as $filterParameterName) {
                $description[$filterParameterName] = [
                    'property' => $property,
                    'type' => $hasAssociation ? 'string' : $this->getPhpType($type),
                    'required' => false,
                ];
            }
        }

        return $description;
    }

    /**
     * Gets the Elasticsearch query corresponding to the current search filter.
     */
    abstract protected function getQuery(string $property, array $values, ?string $nestedPath): array;

    /**
     * Converts the given {@see Type} in PHP type.
     */
    protected function getPhpType(Type $type): string
    {
        switch ($builtinType = $type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_ARRAY:
            case Type::BUILTIN_TYPE_INT:
            case Type::BUILTIN_TYPE_FLOAT:
            case Type::BUILTIN_TYPE_BOOL:
            case Type::BUILTIN_TYPE_STRING:
                return $builtinType;
            case Type::BUILTIN_TYPE_OBJECT:
                if (null !== ($className = $type->getClassName()) && is_a($className, \DateTimeInterface::class, true)) {
                    return \DateTimeInterface::class;
                }

            // no break
            default:
                return 'string';
        }
    }

    /**
     * Is the given property of the given resource class an identifier?
     */
    protected function isIdentifier(string $resourceClass, string $property): bool
    {
        return $property === $this->identifierExtractor->getIdentifierFromResourceClass($resourceClass);
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    protected function getIdentifierValue(string $iri, string $property)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($iri, ['fetch_data' => false])) {
                return $this->propertyAccessor->getValue($item, $property);
            }
        } catch (InvalidArgumentException $e) {
        }

        return $iri;
    }

    /**
     * Are the given values valid according to the given {@see Type}?
     */
    protected function hasValidValues(array $values, Type $type): bool
    {
        foreach ($values as $value) {
            if (
                null !== $value
                && Type::BUILTIN_TYPE_INT === $type->getBuiltinType()
                && false === filter_var($value, \FILTER_VALIDATE_INT)
            ) {
                return false;
            }
        }

        return true;
    }
}
