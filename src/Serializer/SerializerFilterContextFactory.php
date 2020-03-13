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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\Filter\FilterInterface;
use ApiPlatform\Core\Serializer\Filter\SerializerContextFilterInterface;
use Psr\Container\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SerializerFilterContextFactory implements SerializerContextFactoryInterface
{
    private $filterLocator;
    private $resourceMetadataFactory;
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ContainerInterface $filterLocator, SerializerContextFactoryInterface $decorated)
    {
        $this->filterLocator = $filterLocator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $operationName, bool $normalization, array $context): array
    {
        $serializerContext = $this->decorated->create($resourceClass, $operationName, $normalization, $context);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $resourceFilters = $resourceMetadata->getOperationAttribute($context, 'filters', [], true);

        if (!$resourceFilters) {
            return $serializerContext;
        }

        foreach ($resourceFilters as $filterId) {
            if ($this->filterLocator->has($filterId)) {
                $filter = $this->filterLocator->get($filterId);
                if ($filter instanceof SerializerContextFilterInterface) {
                    $filter->applyToSerializerContext($resourceClass, $operationName, $normalization, $context, $serializerContext);
                } elseif ($filter instanceof FilterInterface) {
                    throw new RuntimeException(sprintf('The filter "%s" implements the "%s" interface but "%s" is only compatible with filters implementing the "%s" interface.', \get_class($filter), FilterInterface::class, __CLASS__, SerializerContextFilterInterface::class));
                }
            }
        }

        return $serializerContext;
    }
}
