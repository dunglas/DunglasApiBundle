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

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\AbstractCollectionNormalizer;
use ApiPlatform\Core\Util\IriHelper;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizes collections in the HAL format.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Hamza Amrouche <hamza@les-tilleuls.coop>
 */
final class CollectionNormalizer extends AbstractCollectionNormalizer
{
    public const FORMAT = 'jsonhal';

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, string $pageParameterName, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        parent::__construct($resourceClassResolver, $pageParameterName, $resourceMetadataFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaginationData($object, array $context = []): array
    {
        [$paginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems] = $this->getPaginationConfig($object, $context);
        $parsed = IriHelper::parseIri($context['uri'] ?? '/', $this->pageParameterName);

        $metadata = $this->resourceMetadataFactory->create($context['resource_class'] ?? '');
        $urlGenerationStrategy = $metadata->getAttribute('url_generation_strategy');

        $data = [
            '_links' => [
                'self' => ['href' => IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null, $urlGenerationStrategy)],
            ],
        ];

        if ($paginated) {
            if (null !== $lastPage) {
                $data['_links']['first']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1., $urlGenerationStrategy);
                $data['_links']['last']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage, $urlGenerationStrategy);
            }

            if (1. !== $currentPage) {
                $data['_links']['prev']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1., $urlGenerationStrategy);
            }

            if ((null !== $lastPage && $currentPage !== $lastPage) || (null === $lastPage && $pageTotalItems >= $itemsPerPage)) {
                $data['_links']['next']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1., $urlGenerationStrategy);
            }
        }

        if (null !== $totalItems) {
            $data['totalItems'] = $totalItems;
        }

        if ($paginator) {
            $data['itemsPerPage'] = (int) $itemsPerPage;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    protected function getItemsData($object, string $format = null, array $context = []): array
    {
        $data = [];

        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context);
            if (!\is_array($item)) {
                throw new UnexpectedValueException('Expected item to be an array');
            }
            $data['_embedded']['item'][] = $item;
            $data['_links']['item'][] = $item['_links']['self'];
        }

        return $data;
    }
}
