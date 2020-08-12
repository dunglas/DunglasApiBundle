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

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait as OrmPropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies selected ordering while querying resource collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class OrderExtension implements ContextAwareQueryCollectionExtensionInterface
{
    use OrmPropertyHelperTrait;
    use PropertyHelperTrait;

    public const DIRECTION_ASC = 'ASC';
    public const DIRECTION_DESC = 'DESC';

    private $order;
    private $resourceMetadataFactory;
    private $managerRegistry;
    /**
     * @var EntityManager|null
     */
    private $entityManager;

    public function __construct(string $order = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ManagerRegistry $managerRegistry = null)
    {
        $this->order = $order;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        // BC
        if (null === $this->managerRegistry) {
            $this->entityManager = $queryBuilder->getEntityManager();
        }

        if (null !== $this->resourceMetadataFactory) {
            if (null !== $order = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order')) {
                if (\is_string($order)) {
                    $direction = strtoupper($order);
                    if (\in_array($direction, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
                        $this->addOrderByForAllIdentifiers($queryBuilder, $resourceClass, $direction);

                        return;
                    }

                    $order = [$order];
                }

                foreach ($order as $property => $direction) {
                    if (\is_int($property)) {
                        $property = $direction;
                        $direction = self::DIRECTION_ASC;
                    }

                    $alias = $queryBuilder->getRootAliases()[0];
                    $field = $property;

                    if ($this->isPropertyNested($property, $resourceClass)) {
                        [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
                    }
                    $queryBuilder->addOrderBy(sprintf('%s.%s', $alias, $field), $direction);
                }

                return;
            }
        }

        if (null !== $this->order) {
            $this->addOrderByForAllIdentifiers($queryBuilder, $resourceClass, $this->order);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassMetadata(string $resourceClass): ClassMetadata
    {
        // BC
        if (null === $this->managerRegistry) {
            if (null === $this->entityManager) {
                throw new \RuntimeException('The manager registry was not provided, and the entity manager was not set on the class.');
            }

            return $this->entityManager->getClassMetadata($resourceClass);
        }

        return $this
            ->getManagerRegistry()
            ->getManagerForClass($resourceClass)
            ->getClassMetadata($resourceClass);
    }

    /**
     * {@inheritdoc}
     */
    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    private function addOrderByForAllIdentifiers(QueryBuilder $queryBuilder, string $resourceClass, string $direction): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        foreach ($this->getClassMetadata($resourceClass)->getIdentifier() as $field) {
            $queryBuilder->addOrderBy(sprintf('%s.%s', $rootAlias, $field), $direction);
        }
    }
}
