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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Abstract class for easing the implementation of a filter extension.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractFilterExtension implements RequestBodySearchCollectionExtensionInterface
{
    private $resourceMetadataFactory;
    private $filterLocator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ContainerInterface $filterLocator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->filterLocator = $filterLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(array $requestBody, string $resourceClass, ?string $operationName = null, array $context = []): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        if (!$resourceFilters) {
            return $requestBody;
        }

        $context['filters'] = $context['filters'] ?? [];
        $clauseBody = [];

        foreach ($resourceFilters as $filterId) {
            if ($this->filterLocator->has($filterId) && is_a($filter = $this->filterLocator->get($filterId), $this->getFilterInterface())) {
                $clauseBody = $filter->apply($clauseBody, $resourceClass, $operationName, $context);
            }
        }

        if (!$clauseBody) {
            return $requestBody;
        }

        return $this->alterRequestBody($requestBody, $clauseBody);
    }

    /**
     * Gets the related filter interface.
     */
    abstract protected function getFilterInterface(): string;

    /**
     * Alters the request body.
     */
    abstract protected function alterRequestBody(array $requestBody, array $clauseBody): array;
}
