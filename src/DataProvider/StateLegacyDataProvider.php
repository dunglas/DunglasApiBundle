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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\State\ProviderInterface;

/**
 * State provider legacy bridge to use new Providers with pre-2.7 resources.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
class StateLegacyDataProvider implements DenormalizedIdentifiersAwareItemDataProviderInterface, ContextAwareCollectionDataProviderInterface
{
    public function __construct(private ?ProviderInterface $provider = null)
    {
    }

    public function getItem(string $resourceClass, /* array */ $id, string $operationName = null, array $context = [])
    {
        return $this->provider->provide($resourceClass, (array) $id, $context);
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        return $this->provider->provide($resourceClass, [], $context);
    }
}
