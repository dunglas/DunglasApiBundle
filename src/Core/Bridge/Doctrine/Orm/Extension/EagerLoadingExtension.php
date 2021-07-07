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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\EagerLoadingTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Eager loads relations.
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class EagerLoadingExtension implements ContextAwareQueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    use EagerLoadingTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $classMetadataFactory;
    private $maxJoins;
    private $serializerContextBuilder;
    private $requestStack;

    /**
     * @TODO move $fetchPartial after $forceEager (@soyuka) in 3.0
     */
    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, int $maxJoins = 30, bool $forceEager = true, RequestStack $requestStack = null, SerializerContextBuilderInterface $serializerContextBuilder = null, bool $fetchPartial = false, ClassMetadataFactoryInterface $classMetadataFactory = null)
    {
        if (null !== $this->requestStack) {
            @trigger_error(sprintf('Passing an instance of "%s" is deprecated since version 2.2 and will be removed in 3.0. Use the data provider\'s context instead.', RequestStack::class), \E_USER_DEPRECATED);
        }
        if (null !== $this->serializerContextBuilder) {
            @trigger_error(sprintf('Passing an instance of "%s" is deprecated since version 2.2 and will be removed in 3.0. Use the data provider\'s context instead.', SerializerContextBuilderInterface::class), \E_USER_DEPRECATED);
        }

        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->maxJoins = $maxJoins;
        $this->forceEager = $forceEager;
        $this->fetchPartial = $fetchPartial;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        $this->apply(true, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
    }

    /**
     * {@inheritdoc}
     *
     * The context may contain serialization groups which helps defining joined entities that are readable.
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->apply(false, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
    }

    private function apply(bool $collection, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, ?string $resourceClass, ?string $operationName, array $context)
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $options = [];
        if (null !== $operationName) {
            $options[($collection ? 'collection' : 'item').'_operation_name'] = $operationName;
        }

        $forceEager = $this->shouldOperationForceEager($resourceClass, $options);
        $fetchPartial = $this->shouldOperationFetchPartial($resourceClass, $options);

        if (!isset($context['groups']) && !isset($context['attributes'])) {
            $contextType = isset($context['api_denormalize']) ? 'denormalization_context' : 'normalization_context';
            $context += $this->getNormalizationContext($context['resource_class'] ?? $resourceClass, $contextType, $options);
        }

        if (empty($context[AbstractNormalizer::GROUPS]) && !isset($context[AbstractNormalizer::ATTRIBUTES])) {
            return;
        }

        if (!empty($context[AbstractNormalizer::GROUPS])) {
            $options['serializer_groups'] = (array) $context[AbstractNormalizer::GROUPS];
        }

        $this->joinRelations($queryBuilder, $queryNameGenerator, $resourceClass, $forceEager, $fetchPartial, $queryBuilder->getRootAliases()[0], $options, $context);
    }

    /**
     * Joins relations to eager load.
     *
     * @param bool $wasLeftJoin  if the relation containing the new one had a left join, we have to force the new one to left join too
     * @param int  $joinCount    the number of joins
     * @param int  $currentDepth the current max depth
     *
     * @throws RuntimeException when the max number of joins has been reached
     */
    private function joinRelations(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, bool $forceEager, bool $fetchPartial, string $parentAlias, array $options = [], array $normalizationContext = [], bool $wasLeftJoin = false, int &$joinCount = 0, int $currentDepth = null)
    {
        if ($joinCount > $this->maxJoins) {
            throw new RuntimeException('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary with the "api_platform.eager_loading.max_joins" configuration key (https://api-platform.com/docs/core/performance/#eager-loading), or limit the maximum serialization depth using the "enable_max_depth" option of the Symfony serializer (https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth).');
        }

        $currentDepth = $currentDepth > 0 ? $currentDepth - 1 : $currentDepth;
        $entityManager = $queryBuilder->getEntityManager();
        $classMetadata = $entityManager->getClassMetadata($resourceClass);
        $attributesMetadata = $this->classMetadataFactory ? $this->classMetadataFactory->getMetadataFor($resourceClass)->getAttributesMetadata() : null;

        foreach ($classMetadata->associationMappings as $association => $mapping) {
            //Don't join if max depth is enabled and the current depth limit is reached
            if (0 === $currentDepth && ($normalizationContext[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ?? false)) {
                continue;
            }

            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $association, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                //skip properties not found
                continue;
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                //skip associations that are not resource classes
                continue;
            }

            if (
                // Always skip extra lazy associations
                ClassMetadataInfo::FETCH_EXTRA_LAZY === $mapping['fetch'] ||
                // We don't want to interfere with doctrine on this association
                (false === $forceEager && ClassMetadataInfo::FETCH_EAGER !== $mapping['fetch'])
            ) {
                continue;
            }

            // prepare the child context
            $childNormalizationContext = $normalizationContext;
            if (isset($normalizationContext[AbstractNormalizer::ATTRIBUTES])) {
                if ($inAttributes = isset($normalizationContext[AbstractNormalizer::ATTRIBUTES][$association])) {
                    $childNormalizationContext[AbstractNormalizer::ATTRIBUTES] = $normalizationContext[AbstractNormalizer::ATTRIBUTES][$association];
                }
            } else {
                $inAttributes = null;
            }

            if (
                (null === $fetchEager = $propertyMetadata->getAttribute('fetch_eager')) &&
                (null !== $fetchEager = $propertyMetadata->getAttribute('fetchEager'))
            ) {
                @trigger_error('The "fetchEager" attribute is deprecated since 2.3. Please use "fetch_eager" instead.', \E_USER_DEPRECATED);
            }

            if (false === $fetchEager) {
                continue;
            }

            $isNotReadableLink = false === $propertyMetadata->isReadableLink();
            if (null === $fetchEager && (false === $propertyMetadata->isReadable() || ((null === $inAttributes && $isNotReadableLink) || (false === $inAttributes)))) {
                continue;
            }

            $existingJoin = QueryBuilderHelper::getExistingJoin($queryBuilder, $parentAlias, $association);

            if (null !== $existingJoin) {
                $associationAlias = $existingJoin->getAlias();
                $isLeftJoin = Join::LEFT_JOIN === $existingJoin->getJoinType();
            } else {
                $isNullable = $mapping['joinColumns'][0]['nullable'] ?? true;
                $isLeftJoin = false !== $wasLeftJoin || true === $isNullable;
                $method = $isLeftJoin ? 'leftJoin' : 'innerJoin';

                $associationAlias = $queryNameGenerator->generateJoinAlias($association);
                $queryBuilder->{$method}(sprintf('%s.%s', $parentAlias, $association), $associationAlias);
                ++$joinCount;
            }

            if (true === $fetchPartial) {
                try {
                    $this->addSelect($queryBuilder, $mapping['targetEntity'], $associationAlias, $options);
                } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                    continue;
                }
            } else {
                $queryBuilder->addSelect($associationAlias);
            }

            // Avoid recursive joins
            if ($mapping['targetEntity'] === $resourceClass) {
                // Avoid joining the same association twice (see #1959)
                if (!\in_array($associationAlias, $queryBuilder->getAllAliases(), true)) {
                    $queryBuilder->addSelect($associationAlias);
                }

                continue;
            }

            if (isset($attributesMetadata[$association])) {
                $maxDepth = $attributesMetadata[$association]->getMaxDepth();

                // The current depth is the lowest max depth available in the ancestor tree.
                if (null !== $maxDepth && (null === $currentDepth || $maxDepth < $currentDepth)) {
                    $currentDepth = $maxDepth;
                }
            }

            $this->joinRelations($queryBuilder, $queryNameGenerator, $mapping['targetEntity'], $forceEager, $fetchPartial, $associationAlias, $options, $childNormalizationContext, $isLeftJoin, $joinCount, $currentDepth);
        }
    }

    private function addSelect(QueryBuilder $queryBuilder, string $entity, string $associationAlias, array $propertyMetadataOptions)
    {
        $select = [];
        $entityManager = $queryBuilder->getEntityManager();
        $targetClassMetadata = $entityManager->getClassMetadata($entity);
        if (!empty($targetClassMetadata->subClasses)) {
            $queryBuilder->addSelect($associationAlias);

            return;
        }

        foreach ($this->propertyNameCollectionFactory->create($entity) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($entity, $property, $propertyMetadataOptions);

            if (true === $propertyMetadata->isIdentifier()) {
                $select[] = $property;
                continue;
            }

            // If it's an embedded property see below
            if (!\array_key_exists($property, $targetClassMetadata->embeddedClasses)) {
                //the field test allows to add methods to a Resource which do not reflect real database fields
                if ($targetClassMetadata->hasField($property) && (true === $propertyMetadata->getAttribute('fetchable') || $propertyMetadata->isReadable())) {
                    $select[] = $property;
                }

                continue;
            }

            // It's an embedded property, select relevant subfields
            foreach ($this->propertyNameCollectionFactory->create($targetClassMetadata->embeddedClasses[$property]['class']) as $embeddedProperty) {
                $propertyMetadata = $this->propertyMetadataFactory->create($entity, $property, $propertyMetadataOptions);
                $propertyName = "$property.$embeddedProperty";
                if ($targetClassMetadata->hasField($propertyName) && (true === $propertyMetadata->getAttribute('fetchable') || $propertyMetadata->isReadable())) {
                    $select[] = $propertyName;
                }
            }
        }

        $queryBuilder->addSelect(sprintf('partial %s.{%s}', $associationAlias, implode(',', $select)));
    }

    /**
     * Gets the serializer context.
     *
     * @param string $contextType normalization_context or denormalization_context
     * @param array  $options     represents the operation name so that groups are the one of the specific operation
     */
    private function getNormalizationContext(string $resourceClass, string $contextType, array $options): array
    {
        if (null !== $this->requestStack && null !== $this->serializerContextBuilder && null !== $request = $this->requestStack->getCurrentRequest()) {
            return $this->serializerContextBuilder->createFromRequest($request, 'normalization_context' === $contextType);
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (isset($options['collection_operation_name'])) {
            $context = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], $contextType, null, true);
        } elseif (isset($options['item_operation_name'])) {
            $context = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], $contextType, null, true);
        } else {
            $context = $resourceMetadata->getAttribute($contextType);
        }

        return $context ?? [];
    }
}
