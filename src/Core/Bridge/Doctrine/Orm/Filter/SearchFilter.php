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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final
 */
class SearchFilter extends AbstractContextAwareFilter implements SearchFilterInterface
{
    use SearchFilterTrait;

    public const DOCTRINE_INTEGER_TYPE = DBALType::INTEGER;

    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null, IdentifiersExtractorInterface $identifiersExtractor = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        if (null === $identifiersExtractor) {
            @trigger_error('Not injecting ItemIdentifiersExtractor is deprecated since API Platform 2.5 and can lead to unexpected behaviors, it will not be possible anymore in API Platform 3.0.', \E_USER_DEPRECATED);
        }

        $this->iriConverter = $iriConverter;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
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
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (
            null === $value ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        $caseSensitive = true;
        $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

        // prefixing the strategy with i makes it case insensitive
        if (0 === strpos($strategy, 'i')) {
            $strategy = substr($strategy, 1);
            $caseSensitive = false;
        }

        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field)) {
            if ('id' === $field) {
                $values = array_map([$this, 'getIdFromValue'], $values);
            }

            if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
                ]);

                return;
            }

            $this->addWhereByStrategy($strategy, $queryBuilder, $queryNameGenerator, $alias, $field, $values, $caseSensitive);

            return;
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return;
        }

        $values = array_map([$this, 'getIdFromValue'], $values);
        $associationFieldIdentifier = 'id';
        $doctrineTypeField = $this->getDoctrineFieldType($property, $resourceClass);

        if (null !== $this->identifiersExtractor) {
            $associationResourceClass = $metadata->getAssociationTargetClass($field);
            $associationFieldIdentifier = $this->identifiersExtractor->getIdentifiersFromResourceClass($associationResourceClass)[0];
            $doctrineTypeField = $this->getDoctrineFieldType($associationFieldIdentifier, $associationResourceClass);
        }

        if (!$this->hasValidValues($values, $doctrineTypeField)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
            ]);

            return;
        }

        $associationAlias = $alias;
        $associationField = $field;
        if ($metadata->isCollectionValuedAssociation($associationField)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $associationField);
            $associationField = $associationFieldIdentifier;
        }

        $this->addWhereByStrategy($strategy, $queryBuilder, $queryNameGenerator, $associationAlias, $associationField, $values, $caseSensitive);
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    protected function addWhereByStrategy(string $strategy, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, $values, bool $caseSensitive)
    {
        if (!\is_array($values)) {
            $values = [$values];
        }

        $wrapCase = $this->createWrapCase($caseSensitive);
        $valueParameter = ':'.$queryNameGenerator->generateParameterName($field);
        $aliasedField = sprintf('%s.%s', $alias, $field);

        if (!$strategy || self::STRATEGY_EXACT === $strategy) {
            if (1 === \count($values)) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->eq($wrapCase($aliasedField), $wrapCase($valueParameter)))
                    ->setParameter($valueParameter, $values[0]);

                return;
            }

            $queryBuilder
                ->andWhere($queryBuilder->expr()->in($wrapCase($aliasedField), $valueParameter))
                ->setParameter($valueParameter, $caseSensitive ? $values : array_map('strtolower', $values));

            return;
        }

        $ors = [];
        $parameters = [];
        foreach ($values as $key => $value) {
            $keyValueParameter = sprintf('%s_%s', $valueParameter, $key);
            $parameters[$caseSensitive ? $value : strtolower($value)] = $keyValueParameter;

            switch ($strategy) {
                case self::STRATEGY_PARTIAL:
                    $ors[] = $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string) $queryBuilder->expr()->concat("'%'", $keyValueParameter, "'%'"))
                    );
                    break;
                case self::STRATEGY_START:
                    $ors[] = $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string) $queryBuilder->expr()->concat($keyValueParameter, "'%'"))
                    );
                    break;
                case self::STRATEGY_END:
                    $ors[] = $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string) $queryBuilder->expr()->concat("'%'", $keyValueParameter))
                    );
                    break;
                case self::STRATEGY_WORD_START:
                    $ors[] = $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like($wrapCase($aliasedField), $wrapCase((string) $queryBuilder->expr()->concat($keyValueParameter, "'%'"))),
                        $queryBuilder->expr()->like($wrapCase($aliasedField), $wrapCase((string) $queryBuilder->expr()->concat("'% '", $keyValueParameter, "'%'")))
                    );
                    break;
                default:
                    throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
            }
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$ors));
        array_walk($parameters, [$queryBuilder, 'setParameter']);
    }

    /**
     * Creates a function that will wrap a Doctrine expression according to the
     * specified case sensitivity.
     *
     * For example, "o.name" will get wrapped into "LOWER(o.name)" when $caseSensitive
     * is false.
     */
    protected function createWrapCase(bool $caseSensitive): \Closure
    {
        return static function (string $expr) use ($caseSensitive): string {
            if ($caseSensitive) {
                return $expr;
            }

            return sprintf('LOWER(%s)', $expr);
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType): string
    {
        switch ($doctrineType) {
            case DBALType::TARRAY:
                return 'array';
            case DBALType::BIGINT:
            case DBALType::INTEGER:
            case DBALType::SMALLINT:
                return 'int';
            case DBALType::BOOLEAN:
                return 'bool';
            case DBALType::DATE:
            case DBALType::TIME:
            case DBALType::DATETIME:
            case DBALType::DATETIMETZ:
                return \DateTimeInterface::class;
            case DBALType::FLOAT:
                return 'float';
        }

        if (\defined(DBALType::class.'::DATE_IMMUTABLE')) {
            switch ($doctrineType) {
                case DBALType::DATE_IMMUTABLE:
                case DBALType::TIME_IMMUTABLE:
                case DBALType::DATETIME_IMMUTABLE:
                case DBALType::DATETIMETZ_IMMUTABLE:
                    return \DateTimeInterface::class;
            }
        }

        return 'string';
    }
}
