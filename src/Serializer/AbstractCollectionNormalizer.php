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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Base collection normalizer.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractCollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    use ContextTrait {
        initContext as protected;
    }
    use NormalizerAwareTrait;

    /**
     * This constant must be overridden in the child class.
     */
    public const FORMAT = 'to-override';

    protected $resourceClassResolver;
    protected $pageParameterName;

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, string $pageParameterName)
    {
        $this->resourceClassResolver = $resourceClassResolver;
        $this->pageParameterName = $pageParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return static::FORMAT === $format && is_iterable($data);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!isset($context['resource_class']) || isset($context['api_sub_level'])) {
            return $this->normalizeRawCollection($object, $format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $context = $this->initContext($resourceClass, $context);
        $data = [];

        return array_merge_recursive(
            $data,
            $this->getPaginationData($object, $context),
            $this->getItemsData($object, $format, $context)
        );
    }

    /**
     * Normalizes a raw collection (not API resources).
     *
     * @param string|null $format
     */
    protected function normalizeRawCollection($object, $format = null, array $context = []): array
    {
        $data = [];
        foreach ($object as $index => $obj) {
            $data[$index] = $this->normalizer->normalize($obj, $format, $context);
        }

        return $data;
    }

    /**
     * Gets the pagination configuration.
     *
     * @param iterable $object
     */
    protected function getPaginationConfig($object, array $context = []): array
    {
        $currentPage = null;
        $lastPage = null;
        $itemsPerPage = null;
        $pageTotalItems = null;
        $totalItems = null;
        $paginated = false;
        $isPaginator = false;

        if ($object instanceof PartialPaginatorInterface) {
            $paginated = true;
            $isPaginator = true;
            if ($object instanceof PaginatorInterface) {
                $paginated = true;
                $lastPage = $object->getLastPage();
                $totalItems = $object->getTotalItems();
            } else {
                $pageTotalItems = (float) \count($object);
            }

            $currentPage = $object->getCurrentPage();
            $itemsPerPage = $object->getItemsPerPage();
        } elseif (\is_array($object) || $object instanceof \Countable) {
            $totalItems = \count($object);
        }

        return [$isPaginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems];
    }

    /**
     * Gets the pagination data.
     *
     * @param iterable $object
     */
    abstract protected function getPaginationData($object, array $context = []): array;

    /**
     * Gets items data.
     *
     * @param iterable $object
     */
    abstract protected function getItemsData($object, string $format = null, array $context = []): array;
}
