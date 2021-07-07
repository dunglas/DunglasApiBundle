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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider;

use ApiPlatform\Core\DataProvider\ChainCollectionDataProvider;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictDataProviderTrait;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class TraceableChainCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    use RestrictDataProviderTrait;

    private $context = [];
    private $providersResponse = [];

    public function __construct(CollectionDataProviderInterface $collectionDataProvider)
    {
        if ($collectionDataProvider instanceof ChainCollectionDataProvider) {
            $this->dataProviders = $collectionDataProvider->dataProviders;
        }
    }

    public function getProvidersResponse(): array
    {
        return $this->providersResponse;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $this->context = $context;
        $results = null;
        $match = false;

        foreach ($this->dataProviders as $dataProvider) {
            $this->providersResponse[\get_class($dataProvider)] = $match ? null : false;
            if ($match) {
                continue;
            }
            try {
                if ($dataProvider instanceof RestrictedDataProviderInterface
                    && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    continue;
                }

                $results = $dataProvider->getCollection($resourceClass, $operationName, $context);
                $this->providersResponse[\get_class($dataProvider)] = $match = true;
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" in a data provider is deprecated in favor of implementing "%s"', ResourceClassNotSupportedException::class, RestrictedDataProviderInterface::class), \E_USER_DEPRECATED);
                continue;
            }
        }

        return $results ?? [];
    }
}
