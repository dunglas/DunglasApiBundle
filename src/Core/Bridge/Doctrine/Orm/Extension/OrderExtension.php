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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
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
    private $order;
    private $resourceMetadataFactory;

    /**
     * @param ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
     */
    public function __construct(string $order = null, $resourceMetadataFactory = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($this->resourceMetadataFactory) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be replaced by %s in 3.0.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class), \E_USER_DEPRECATED);
        }

        $this->order = $order;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $classMetaData = $queryBuilder->getEntityManager()->getClassMetadata($resourceClass);
        $identifiers = $classMetaData->getIdentifier();
        if (null !== $this->resourceMetadataFactory) {
            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName)->getOrder();
            } else {
                // TODO: remove in 3.0
                $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)
                    ->getCollectionOperationAttribute($operationName, 'order', [], true);
                if (empty($defaultOrder)) {
                    $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
                }
            }
            if (null !== $defaultOrder) {
                foreach ($defaultOrder as $field => $order) {
                    if (\is_int($field)) {
                        // Default direction
                        $field = $order;
                        $order = 'ASC';
                    }

                    $pos = strpos($field, '.');
                    if (false === $pos || isset($classMetaData->embeddedClasses[substr($field, 0, $pos)])) {
                        // Configure default filter with property
                        $field = "{$rootAlias}.{$field}";
                    } else {
                        $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $rootAlias, substr($field, 0, $pos));
                        $field = sprintf('%s.%s', $alias, substr($field, $pos + 1));
                    }
                    $queryBuilder->addOrderBy($field, $order);
                }

                return;
            }
        }

        if (null !== $this->order) {
            // A foreign identifier cannot be used for ordering.
            if ($classMetaData->containsForeignIdentifier) {
                return;
            }

            foreach ($identifiers as $identifier) {
                $queryBuilder->addOrderBy("{$rootAlias}.{$identifier}", $this->order);
            }
        }
    }
}
