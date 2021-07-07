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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Paginator;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Applies pagination on the Doctrine aggregation for resource collection when enabled.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PaginationExtension implements AggregationResultCollectionExtensionInterface
{
    private $managerRegistry;
    private $resourceMetadataFactory;
    private $pagination;

    public function __construct(ManagerRegistry $managerRegistry, ResourceMetadataFactoryInterface $resourceMetadataFactory, Pagination $pagination)
    {
        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->pagination = $pagination;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (!$this->pagination->isEnabled($resourceClass, $operationName, $context)) {
            return;
        }

        if (($context['graphql_operation_name'] ?? false) && !$this->pagination->isGraphQlEnabled($resourceClass, $operationName, $context)) {
            return;
        }

        $context = $this->addCountToContext(clone $aggregationBuilder, $context);

        [, $offset, $limit] = $this->pagination->getPagination($resourceClass, $operationName, $context);

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new RuntimeException(sprintf('The manager for "%s" must be an instance of "%s".', $resourceClass, DocumentManager::class));
        }

        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $resultsAggregationBuilder = $repository->createAggregationBuilder()->skip($offset);
        if ($limit > 0) {
            $resultsAggregationBuilder->limit($limit);
        } else {
            // Results have to be 0 but MongoDB does not support a limit equal to 0.
            $resultsAggregationBuilder->match()->field(Paginator::LIMIT_ZERO_MARKER_FIELD)->equals(Paginator::LIMIT_ZERO_MARKER);
        }

        $aggregationBuilder
            ->facet()
            ->field('results')->pipeline(
                $resultsAggregationBuilder
            )
            ->field('count')->pipeline(
                $repository->createAggregationBuilder()
                    ->count('count')
            );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        if ($context['graphql_operation_name'] ?? false) {
            return $this->pagination->isGraphQlEnabled($resourceClass, $operationName, $context);
        }

        return $this->pagination->isEnabled($resourceClass, $operationName, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getResult(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array $context = [])
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new RuntimeException(sprintf('The manager for "%s" must be an instance of "%s".', $resourceClass, DocumentManager::class));
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $attribute = $resourceMetadata->getCollectionOperationAttribute($operationName, 'doctrine_mongodb', [], true);
        $executeOptions = $attribute['execute_options'] ?? [];

        return new Paginator($aggregationBuilder->execute($executeOptions), $manager->getUnitOfWork(), $resourceClass, $aggregationBuilder->getPipeline());
    }

    private function addCountToContext(Builder $aggregationBuilder, array $context): array
    {
        if (!($context['graphql_operation_name'] ?? false)) {
            return $context;
        }

        if (isset($context['filters']['last']) && !isset($context['filters']['before'])) {
            $context['count'] = $aggregationBuilder->count('count')->execute()->toArray()[0]['count'];
        }

        return $context;
    }
}
