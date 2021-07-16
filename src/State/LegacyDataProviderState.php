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

namespace ApiPlatform\State;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;

class LegacyDataProviderState implements ProviderInterface
{
    public function __construct(private ItemDataProviderInterface $itemDataProvider, private CollectionDataProviderInterface $collectionDataProvider, private SubresourceDataProviderInterface $subresourceDataProvider)
    {
    }

    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        $operation = $context['operation'] ?? null;
        if ($operation && ($operation->getExtraProperties()['is_legacy_subresource'] ?? false)) {
            $subresourceContext = ['identifiers' => $operation->getExtraProperties()['legacy_subresource_identifiers'], 'filters' => $context['filters'] ?? []] + $context;
            $subresourceIdentifiers = [];
            foreach ($operation->getIdentifiers() as $parameterName => [$class, $property]) {
                $subresourceIdentifiers[$parameterName] = [$property => $identifiers[$parameterName]];
            }

            return $this->subresourceDataProvider->getSubresource($resourceClass, $subresourceIdentifiers, $subresourceContext, $operationName);
        }

        if ($identifiers) {
            return $this->itemDataProvider->getItem($resourceClass, $identifiers, $operationName, $context);
        }

        if ($this->collectionDataProvider instanceof ContextAwareCollectionDataProviderInterface) {
            return $this->collectionDataProvider->getCollection($resourceClass, $operationName, $context);
        }

        return $this->collectionDataProvider->getCollection($resourceClass, $operationName);
    }

    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        if (!$this->collectionDataProvider instanceof RestrictedDataProviderInterface && !$this->itemDataProvider instanceof RestrictedDataProviderInterface) {
            return false;
        }

        if ($identifiers) {
            return $this->itemDataProvider->supports($resourceClass, $operationName, $context);
        }

        return $this->collectionDataProvider->supports($resourceClass, $operationName, $context);
    }
}
