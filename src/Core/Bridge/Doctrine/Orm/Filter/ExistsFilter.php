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

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @final
 */
class ExistsFilter extends AbstractContextAwareFilter implements ExistsFilterInterface
{
    use ExistsFilterTrait;

    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, string $existsParameterName = self::QUERY_PARAMETER_KEY, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        $this->existsParameterName = $existsParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (!\is_array($context['filters'][$this->existsParameterName] ?? null)) {
            $context['exists_deprecated_syntax'] = true;
            parent::apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

            return;
        }

        foreach ($context['filters'][$this->existsParameterName] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null/*, array $context = []*/)
    {
        if (\func_num_args() > 6) {
            $context = func_get_arg(6);
        } else {
            if (__CLASS__ !== static::class) { /** @phpstan-ignore-line The class was not final before */
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a seventh `$context` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.5.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }
            $context = [];
        }

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

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasAssociation($field)) {
            if ($metadata->isCollectionValuedAssociation($field)) {
                $queryBuilder
                    ->andWhere(sprintf('%s.%s %s EMPTY', $alias, $field, $value ? 'IS NOT' : 'IS'));

                return;
            }

            if ($metadata->isAssociationInverseSide($field)) {
                $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $field, Join::LEFT_JOIN);

                $queryBuilder
                    ->andWhere(sprintf('%s %s NULL', $alias, $value ? 'IS NOT' : 'IS'));

                return;
            }

            $queryBuilder
                ->andWhere(sprintf('%s.%s %s NULL', $alias, $field, $value ? 'IS NOT' : 'IS'));

            return;
        }

        if ($metadata->hasField($field)) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s %s NULL', $alias, $field, $value ? 'IS NOT' : 'IS'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isNullableField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        $field = $propertyParts['field'];

        if ($metadata->hasAssociation($field)) {
            if ($metadata->isSingleValuedAssociation($field)) {
                if (!($metadata instanceof ClassMetadataInfo)) {
                    return false;
                }

                $associationMapping = $metadata->getAssociationMapping($field);

                return $this->isAssociationNullable($associationMapping);
            }

            return true;
        }

        if ($metadata instanceof ClassMetadataInfo && $metadata->hasField($field)) {
            return $metadata->isNullable($field);
        }

        return false;
    }

    /**
     * Determines whether an association is nullable.
     *
     * @see https://github.com/doctrine/doctrine2/blob/v2.5.4/lib/Doctrine/ORM/Tools/EntityGenerator.php#L1221-L1246
     */
    private function isAssociationNullable(array $associationMapping): bool
    {
        if (!empty($associationMapping['id'])) {
            return false;
        }

        if (!isset($associationMapping['joinColumns'])) {
            return true;
        }

        $joinColumns = $associationMapping['joinColumns'];
        foreach ($joinColumns as $joinColumn) {
            if (isset($joinColumn['nullable']) && !$joinColumn['nullable']) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractProperties(Request $request/*, string $resourceClass*/): array
    {
        if (!$request->query->has($this->existsParameterName)) {
            $resourceClass = \func_num_args() > 1 ? (string) func_get_arg(1) : null;

            return parent::extractProperties($request, $resourceClass);
        }

        @trigger_error(sprintf('The use of "%s::extractProperties()" is deprecated since 2.2. Use the "filters" key of the context instead.', __CLASS__), \E_USER_DEPRECATED);

        $properties = $request->query->all()[$this->existsParameterName];

        return \is_array($properties) ? $properties : [];
    }
}
