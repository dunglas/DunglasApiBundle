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
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class SerializerFilterContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $filterLocator;
    private $resourceMetadataFactory;

    public function __construct($resourceMetadataFactory, ContainerInterface $filterLocator, SerializerContextBuilderInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->filterLocator = $filterLocator;
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be not be used anymore in 3.0.', ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        $context = $this->decorated->createFromRequest($request, $normalization, $attributes);

        // TODO: remove in 3.0
        if (
            !$this->resourceMetadataFactory
            && isset($attributes['operation_name']) && isset($context['filters'])
        ) {
            $resourceFilters = $context['filters'];
        } elseif ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
            $resourceFilters = $resourceMetadata->getOperationAttribute($attributes, 'filters', [], true);
        } else {
            $resourceFilters = $this->resourceMetadataFactory->create($attributes['resource_class'])->getOperation($attributes['operation_name'])->getFilters();
        }

        if (!$resourceFilters) {
            return $context;
        }

        foreach ($resourceFilters as $filterId) {
            if ($this->filterLocator->has($filterId) && ($filter = $this->filterLocator->get($filterId)) instanceof FilterInterface) {
                $filter->apply($request, $normalization, $attributes, $context);
            }
        }

        return $context;
    }
}
