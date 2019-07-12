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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Cache\QueryExpander;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Collection data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @final
 */
class CollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $managerRegistry;
    private $collectionExtensions;
    private $queryExpander;

    /**
     * @param QueryCollectionExtensionInterface[]|ContextAwareQueryCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, QueryExpander $queryExpander, /* iterable */ $collectionExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->collectionExtensions = $collectionExtensions;
        $this->queryExpander = $queryExpander;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return null !== $this->managerRegistry->getManagerForClass($resourceClass);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     * @throws ResourceClassNotFoundException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        /** @var ObjectManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
            }
        }
        $query = $queryBuilder->getQuery();
        $this->queryExpander->expand($resourceClass, $query);

        return $query->getResult();
    }
}
