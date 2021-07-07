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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filter the collection by given properties.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SearchFilter extends AbstractFilter implements SearchFilterInterface
{
    use SearchFilterTrait;

    public const DOCTRINE_INTEGER_TYPE = [MongoDbType::INTEGER, MongoDbType::INT];

    public function __construct(ManagerRegistry $managerRegistry, IriConverterInterface $iriConverter, IdentifiersExtractorInterface $identifiersExtractor, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->identifiersExtractor = $identifiersExtractor;
    }

    protected function getIriConverter(): IriConverterInterface
    {
        return $this->iriConverter;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (
            null === $value ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $matchField = $field = $property;

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField, $field, $associations] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        $caseSensitive = true;
        $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

        // prefixing the strategy with i makes it case insensitive
        if (0 === strpos($strategy, 'i')) {
            $strategy = substr($strategy, 1);
            $caseSensitive = false;
        }

        /** @var MongoDBClassMetadata */
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field) && !$metadata->hasAssociation($field)) {
            if ('id' === $field) {
                $values = array_map([$this, 'getIdFromValue'], $values);
            }

            if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
                ]);

                return;
            }

            $this->addEqualityMatchStrategy($strategy, $aggregationBuilder, $field, $matchField, $values, $caseSensitive, $metadata);

            return;
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return;
        }

        $values = array_map([$this, 'getIdFromValue'], $values);
        $doctrineTypeField = $this->getDoctrineFieldType($property, $resourceClass);

        if (null !== $this->identifiersExtractor) {
            $associationResourceClass = $metadata->getAssociationTargetClass($field);
            $associationFieldIdentifier = $this->identifiersExtractor->getIdentifiersFromResourceClass($associationResourceClass)[0];
            $doctrineTypeField = $this->getDoctrineFieldType($associationFieldIdentifier, $associationResourceClass);
        }

        if (!$this->hasValidValues($values, $doctrineTypeField)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $property)),
            ]);

            return;
        }

        $this->addEqualityMatchStrategy($strategy, $aggregationBuilder, $field, $matchField, $values, $caseSensitive, $metadata);
    }

    /**
     * Add equality match stage according to the strategy.
     */
    private function addEqualityMatchStrategy(string $strategy, Builder $aggregationBuilder, string $field, string $matchField, $values, bool $caseSensitive, ClassMetadata $metadata): void
    {
        $inValues = [];
        foreach ($values as $inValue) {
            $inValues[] = $this->getEqualityMatchStrategyValue($strategy, $field, $inValue, $caseSensitive, $metadata);
        }

        $aggregationBuilder
            ->match()
            ->field($matchField)
            ->in($inValues);
    }

    /**
     * Get equality match value according to the strategy.
     *
     * @throws InvalidArgumentException If strategy does not exist
     *
     * @return Regex|string
     */
    private function getEqualityMatchStrategyValue(string $strategy, string $field, $value, bool $caseSensitive, ClassMetadata $metadata)
    {
        $type = $metadata->getTypeOfField($field);

        if (!MongoDbType::hasType($type)) {
            return $value;
        }
        if (MongoDbType::STRING !== $type) {
            return MongoDbType::getType($type)->convertToDatabaseValue($value);
        }

        $quotedValue = preg_quote($value);

        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                return $caseSensitive ? $value : new Regex("^$quotedValue$", 'i');
            case self::STRATEGY_PARTIAL:
                return new Regex($quotedValue, $caseSensitive ? '' : 'i');
            case self::STRATEGY_START:
                return new Regex("^$quotedValue", $caseSensitive ? '' : 'i');
            case self::STRATEGY_END:
                return new Regex("$quotedValue$", $caseSensitive ? '' : 'i');
            case self::STRATEGY_WORD_START:
                return new Regex("(^$quotedValue.*|.*\s$quotedValue.*)", $caseSensitive ? '' : 'i');
            default:
                throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType): string
    {
        switch ($doctrineType) {
            case MongoDbType::INT:
            case MongoDbType::INTEGER:
                return 'int';
            case MongoDbType::BOOL:
            case MongoDbType::BOOLEAN:
                return 'bool';
            case MongoDbType::DATE:
            case MongoDbType::DATE_IMMUTABLE:
                return \DateTimeInterface::class;
            case MongoDbType::FLOAT:
                return 'float';
        }

        return 'string';
    }
}
