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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\IdentifierManagerTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Item data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @final
 */
class ItemDataProvider implements DenormalizedIdentifiersAwareItemDataProviderInterface, RestrictedDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $itemExtensions;

    /**
     * @param QueryItemExtensionInterface[] $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $itemExtensions = [], ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->itemExtensions = $itemExtensions;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->managerRegistry->getManagerForClass($resourceClass) instanceof EntityManagerInterface;
    }

    /**
     * {@inheritdoc}
     *
     * The context may contain a `fetch_data` key representing whether the value should be fetched by Doctrine or if we should return a reference.
     *
     * @throws RuntimeException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        if ((\is_int($id) || \is_string($id)) && !($context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] ?? false)) {
            $id = $this->normalizeIdentifiers($id, $manager, $resourceClass);
        }
        if (!\is_array($id)) {
            throw new \InvalidArgumentException(sprintf('$id must be array when "%s" key is set to true in the $context', IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER));
        }
        $identifiers = $id;

        $fetchData = $context['fetch_data'] ?? true;
        if (!$fetchData) {
            return $manager->getReference($resourceClass, $identifiers);
        }

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);

        $this->addWhereForIdentifiers($identifiers, $queryBuilder, $doctrineClassMetadata);

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);

            if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
            }
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Add WHERE conditions to the query for one or more identifiers (simple or composite).
     */
    private function addWhereForIdentifiers(array $identifiers, QueryBuilder $queryBuilder, ClassMetadata $classMetadata)
    {
        $alias = $queryBuilder->getRootAliases()[0];
        foreach ($identifiers as $identifier => $value) {
            $placeholder = ':id_'.$identifier;
            $expression = $queryBuilder->expr()->eq(
                "{$alias}.{$identifier}",
                $placeholder
            );

            $queryBuilder->andWhere($expression);

            $queryBuilder->setParameter($placeholder, $value, $classMetadata->getTypeOfField($identifier));
        }
    }
}
