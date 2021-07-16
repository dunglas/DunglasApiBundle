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
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Item data provider for the Doctrine MongoDB ODM.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ItemDataProvider implements DenormalizedIdentifiersAwareItemDataProviderInterface, RestrictedDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $resourceMetadataFactory;
    private $itemExtensions;

    /**
     * @param AggregationItemExtensionInterface[] $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemExtensions = $itemExtensions;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->managerRegistry->getManagerForClass($resourceClass) instanceof DocumentManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        if (!\is_array($id) && !($context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] ?? false)) {
            $id = $this->normalizeIdentifiers($id, $manager, $resourceClass);
        }

        $id = (array) $id;

        if (!($context['fetch_data'] ?? true)) {
            return $manager->getReference($resourceClass, reset($id));
        }

        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();

        foreach ($id as $propertyName => $value) {
            $aggregationBuilder->match()->field($propertyName)->equals($value);
        }

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($aggregationBuilder, $resourceClass, $id, $operationName, $context);

            if ($extension instanceof AggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
            }
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $attribute = $resourceMetadata->getItemOperationAttribute($operationName, 'doctrine_mongodb', [], true);
        $executeOptions = $attribute['execute_options'] ?? [];

        return $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions)->current() ?: null;
    }
}
