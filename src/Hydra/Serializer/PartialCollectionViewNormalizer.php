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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Util\IriHelper;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds a view key to the result of a paginated Hydra collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PartialCollectionViewNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    private $collectionNormalizer;
    private $pageParameterName;
    private $enabledParameterName;

    public function __construct(NormalizerInterface $collectionNormalizer, string $pageParameterName = 'page', string $enabledParameterName = 'pagination')
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->pageParameterName = $pageParameterName;
        $this->enabledParameterName = $enabledParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);
        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }

        if (isset($context['api_sub_level'])) {
            return $data;
        }

        $currentPage = null;
        $lastPage = null;
        $itemsPerPage = null;
        $pageTotalItems = null;
        if ($paginated = $object instanceof PartialPaginatorInterface) {
            if ($object instanceof PaginatorInterface) {
                $paginated = true;
                $lastPage = $object->getLastPage();
            } else {
                $itemsPerPage = $object->getItemsPerPage();
                $pageTotalItems = (float) \count($object);
            }

            $currentPage = $object->getCurrentPage();
        }

        $parsed = IriHelper::parseIri($context['request_uri'] ?? '/', $this->pageParameterName);
        $appliedFilters = $parsed['parameters'];
        unset($appliedFilters[$this->enabledParameterName]);

        if (!$appliedFilters && !$paginated) {
            return $data;
        }

        $data['hydra:view'] = [
            '@id' => IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null),
            '@type' => 'hydra:PartialCollectionView',
        ];

        if ($paginated) {
            if (null !== $lastPage) {
                $data['hydra:view']['hydra:first'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1.);
                $data['hydra:view']['hydra:last'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage);
            }

            if (1. !== $currentPage) {
                $data['hydra:view']['hydra:previous'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1.);
            }

            if (null !== $lastPage && $currentPage !== $lastPage || null === $lastPage && $pageTotalItems >= $itemsPerPage) {
                $data['hydra:view']['hydra:next'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1.);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return $this->collectionNormalizer instanceof CacheableSupportsMethodInterface && $this->collectionNormalizer->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function setNormalizer(NormalizerInterface $normalizer)
    {
        if ($this->collectionNormalizer instanceof NormalizerAwareInterface) {
            $this->collectionNormalizer->setNormalizer($normalizer);
        }
    }
}
