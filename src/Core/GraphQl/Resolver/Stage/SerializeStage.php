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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Serialize stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SerializeStage implements SerializeStageInterface
{
    use IdentifierTrait;

    private $resourceMetadataFactory;
    private $normalizer;
    private $serializerContextBuilder;
    private $pagination;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, NormalizerInterface $normalizer, SerializerContextBuilderInterface $serializerContextBuilder, Pagination $pagination)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->normalizer = $normalizer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->pagination = $pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($itemOrCollection, string $resourceClass, string $operationName, array $context): ?array
    {
        $isCollection = $context['is_collection'];
        $isMutation = $context['is_mutation'];
        $isSubscription = $context['is_subscription'];

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (!$resourceMetadata->getGraphqlAttribute($operationName, 'serialize', true, true)) {
            if ($isCollection) {
                if ($this->pagination->isGraphQlEnabled($resourceClass, $operationName, $context)) {
                    return 'cursor' === $this->pagination->getGraphQlPaginationType($resourceClass, $operationName) ?
                        $this->getDefaultCursorBasedPaginatedData() :
                        $this->getDefaultPageBasedPaginatedData();
                }

                return [];
            }

            if ($isMutation) {
                return $this->getDefaultMutationData($context);
            }

            if ($isSubscription) {
                return $this->getDefaultSubscriptionData($context);
            }

            return null;
        }

        $normalizationContext = $this->serializerContextBuilder->create($resourceClass, $operationName, $context, true);

        $data = null;
        if (!$isCollection) {
            if ($isMutation && 'delete' === $operationName) {
                $data = ['id' => $this->getIdentifierFromContext($context)];
            } else {
                $data = $this->normalizer->normalize($itemOrCollection, ItemNormalizer::FORMAT, $normalizationContext);
            }
        }

        if ($isCollection && is_iterable($itemOrCollection)) {
            if (!$this->pagination->isGraphQlEnabled($resourceClass, $operationName, $context)) {
                $data = [];
                foreach ($itemOrCollection as $index => $object) {
                    $data[$index] = $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext);
                }
            } else {
                $data = 'cursor' === $this->pagination->getGraphQlPaginationType($resourceClass, $operationName) ?
                    $this->serializeCursorBasedPaginatedCollection($itemOrCollection, $normalizationContext, $context) :
                    $this->serializePageBasedPaginatedCollection($itemOrCollection, $normalizationContext);
            }
        }

        if (null !== $data && !\is_array($data)) {
            throw new \UnexpectedValueException('Expected serialized data to be a nullable array.');
        }

        if ($isMutation || $isSubscription) {
            $wrapFieldName = lcfirst($resourceMetadata->getShortName());

            return [$wrapFieldName => $data] + ($isMutation ? $this->getDefaultMutationData($context) : $this->getDefaultSubscriptionData($context));
        }

        return $data;
    }

    /**
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    private function serializeCursorBasedPaginatedCollection(iterable $collection, array $normalizationContext, array $context): array
    {
        $args = $context['args'];

        if (!($collection instanceof PartialPaginatorInterface)) {
            throw new \LogicException(sprintf('Collection returned by the collection data provider must implement %s or %s.', PaginatorInterface::class, PartialPaginatorInterface::class));
        }

        $offset = 0;
        $totalItems = 1; // For partial pagination, always consider there is at least one item.
        $nbPageItems = $collection->count();
        if (isset($args['after'])) {
            $after = base64_decode($args['after'], true);
            if (false === $after || '' === $args['after']) {
                throw new \UnexpectedValueException('' === $args['after'] ? 'Empty cursor is invalid' : sprintf('Cursor %s is invalid', $args['after']));
            }
            $offset = 1 + (int) $after;
        }

        if ($collection instanceof PaginatorInterface) {
            $totalItems = $collection->getTotalItems();

            if (isset($args['before'])) {
                $before = base64_decode($args['before'], true);
                if (false === $before || '' === $args['before']) {
                    throw new \UnexpectedValueException('' === $args['before'] ? 'Empty cursor is invalid' : sprintf('Cursor %s is invalid', $args['before']));
                }
                $offset = (int) $before - $nbPageItems;
            }
            if (isset($args['last']) && !isset($args['before'])) {
                $offset = $totalItems - $args['last'];
            }
        }

        $offset = 0 > $offset ? 0 : $offset;

        $data = $this->getDefaultCursorBasedPaginatedData();
        if ($totalItems > 0) {
            $data['pageInfo']['startCursor'] = base64_encode((string) $offset);
            $end = $offset + $nbPageItems - 1;
            $data['pageInfo']['endCursor'] = base64_encode((string) ($end >= 0 ? $end : 0));
            $data['pageInfo']['hasPreviousPage'] = $offset > 0;
            if ($collection instanceof PaginatorInterface) {
                $data['totalCount'] = $totalItems;
                $itemsPerPage = $collection->getItemsPerPage();
                $data['pageInfo']['hasNextPage'] = (float) ($itemsPerPage > 0 ? $offset % $itemsPerPage : $offset) + $itemsPerPage * $collection->getCurrentPage() < $totalItems;
            }
        }

        $index = 0;
        foreach ($collection as $object) {
            $data['edges'][$index] = [
                'node' => $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext),
                'cursor' => base64_encode((string) ($index + $offset)),
            ];
            ++$index;
        }

        return $data;
    }

    /**
     * @throws \LogicException
     */
    private function serializePageBasedPaginatedCollection(iterable $collection, array $normalizationContext): array
    {
        if (!($collection instanceof PaginatorInterface)) {
            throw new \LogicException(sprintf('Collection returned by the collection data provider must implement %s.', PaginatorInterface::class));
        }

        $data = $this->getDefaultPageBasedPaginatedData();
        $data['paginationInfo']['totalCount'] = $collection->getTotalItems();
        $data['paginationInfo']['lastPage'] = $collection->getLastPage();
        $data['paginationInfo']['itemsPerPage'] = $collection->getItemsPerPage();

        foreach ($collection as $object) {
            $data['collection'][] = $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext);
        }

        return $data;
    }

    private function getDefaultCursorBasedPaginatedData(): array
    {
        return ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]];
    }

    private function getDefaultPageBasedPaginatedData(): array
    {
        return ['collection' => [], 'paginationInfo' => ['itemsPerPage' => 0., 'totalCount' => 0., 'lastPage' => 0.]];
    }

    private function getDefaultMutationData(array $context): array
    {
        return ['clientMutationId' => $context['args']['input']['clientMutationId'] ?? null];
    }

    private function getDefaultSubscriptionData(array $context): array
    {
        return ['clientSubscriptionId' => $context['args']['input']['clientSubscriptionId'] ?? null];
    }
}
