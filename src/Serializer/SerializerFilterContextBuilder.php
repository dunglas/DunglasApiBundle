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
 *
 * @deprecated since API Platform 2.6, use {@see \ApiPlatform\Core\Serializer\SerializerFilterContextFactory} class instead
 */
final class SerializerFilterContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $filterLocator;
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ContainerInterface $filterLocator, SerializerContextBuilderInterface $decorated)
    {
        trigger_deprecation('API Platform', '2.6', 'Using "%s" class is deprecated, use "%s" class instead.', __CLASS__, SerializerFilterContextFactory::class);

        $this->decorated = $decorated;
        $this->filterLocator = $filterLocator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        trigger_deprecation('API Platform', '2.6', 'Using "%s()" method is deprecated, use "%s::create()" method instead.', __METHOD__, SerializerFilterContextFactory::class);

        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        $context = $this->decorated->createFromRequest($request, $normalization, $attributes);
        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

        $resourceFilters = $resourceMetadata->getOperationAttribute($attributes, 'filters', [], true);

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
