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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm;

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\IdentifierManagerTrait;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Subresource data provider for the Doctrine MongoDB ODM.
 *
 * @experimental
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubresourceDataProvider implements SubresourceDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $resourceMetadataFactory;
    private $collectionExtensions;
    private $itemExtensions;

    /**
     * @param AggregationCollectionExtensionInterface[] $collectionExtensions
     * @param AggregationItemExtensionInterface[]       $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $collectionExtensions = [], iterable $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
        $this->itemExtensions = $itemExtensions;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new ResourceClassNotSupportedException(sprintf('The manager for "%s" must be an instance of "%s".', $resourceClass, DocumentManager::class));
        }

        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        if (!isset($context['identifiers'], $context['property'])) {
            throw new ResourceClassNotSupportedException('The given resource class is not a subresource.');
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $attribute = $resourceMetadata->getSubresourceOperationAttribute($operationName, 'doctrine_mongodb', [], true);
        $executeOptions = $attribute['execute_options'] ?? [];

        $aggregationBuilder = $this->buildAggregation($identifiers, $context, $executeOptions, $repository->createAggregationBuilder(), \count($context['identifiers']));

        if (true === $context['collection']) {
            foreach ($this->collectionExtensions as $extension) {
                $extension->applyToCollection($aggregationBuilder, $resourceClass, $operationName, $context);
                if ($extension instanceof AggregationResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
                }
            }
        } else {
            foreach ($this->itemExtensions as $extension) {
                $extension->applyToItem($aggregationBuilder, $resourceClass, $identifiers, $operationName, $context);
                if ($extension instanceof AggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
                }
            }
        }

        $iterator = $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions);

        return $context['collection'] ? $iterator->toArray() : ($iterator->current() ?: null);
    }

    /**
     * @throws RuntimeException
     */
    private function buildAggregation(array $identifiers, array $context, array $executeOptions, Builder $previousAggregationBuilder, int $remainingIdentifiers, Builder $topAggregationBuilder = null): Builder
    {
        if ($remainingIdentifiers <= 0) {
            return $previousAggregationBuilder;
        }

        $topAggregationBuilder = $topAggregationBuilder ?? $previousAggregationBuilder;

        if (\is_string(key($context['identifiers']))) {
            $contextIdentifiers = array_keys($context['identifiers']);
            $identifier = $contextIdentifiers[$remainingIdentifiers - 1];
            $identifierResourceClass = $context['identifiers'][$identifier][0];
            $previousAssociationProperty = $contextIdentifiers[$remainingIdentifiers] ?? $context['property'];
        } else {
            @trigger_error('Identifiers should match the convention introduced in ADR 0001-resource-identifiers, this behavior will be removed in 3.0.', \E_USER_DEPRECATED);
            [$identifier, $identifierResourceClass] = $context['identifiers'][$remainingIdentifiers - 1];
            $previousAssociationProperty = $context['identifiers'][$remainingIdentifiers][0] ?? $context['property'];
        }

        $manager = $this->managerRegistry->getManagerForClass($identifierResourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new RuntimeException(sprintf('The manager for "%s" must be an instance of "%s".', $identifierResourceClass, DocumentManager::class));
        }

        $classMetadata = $manager->getClassMetadata($identifierResourceClass);

        if (!$classMetadata instanceof ClassMetadata) {
            throw new RuntimeException(sprintf('The class metadata for "%s" must be an instance of "%s".', $identifierResourceClass, ClassMetadata::class));
        }

        $aggregation = $manager->createAggregationBuilder($identifierResourceClass);
        $normalizedIdentifiers = [];

        if (isset($identifiers[$identifier])) {
            // if it's an array it's already normalized, the IdentifierManagerTrait is deprecated
            if ($context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] ?? false) {
                $normalizedIdentifiers = $identifiers[$identifier];
            } else {
                $normalizedIdentifiers = $this->normalizeIdentifiers($identifiers[$identifier], $manager, $identifierResourceClass);
            }
        }

        if ($classMetadata->hasAssociation($previousAssociationProperty)) {
            $aggregation->lookup($previousAssociationProperty)->alias($previousAssociationProperty);
            foreach ($normalizedIdentifiers as $key => $value) {
                $aggregation->match()->field($key)->equals($value);
            }
        } elseif ($classMetadata->isIdentifier($previousAssociationProperty)) {
            foreach ($normalizedIdentifiers as $key => $value) {
                $aggregation->match()->field($key)->equals($value);
            }

            return $aggregation;
        }

        // Recurse aggregations
        $aggregation = $this->buildAggregation($identifiers, $context, $executeOptions, $aggregation, --$remainingIdentifiers, $topAggregationBuilder);

        $results = $aggregation->execute($executeOptions)->toArray();
        $in = array_reduce($results, static function ($in, $result) use ($previousAssociationProperty) {
            return $in + array_map(static function ($result) {
                return $result['_id'];
            }, $result[$previousAssociationProperty] ?? []);
        }, []);
        $previousAggregationBuilder->match()->field('_id')->in($in);

        return $previousAggregationBuilder;
    }
}
