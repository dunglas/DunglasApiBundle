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

use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * Tries each configured data provider and returns the result of the first able to handle the resource class.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChainCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    use RestrictDataProviderTrait;

    /**
     * @param CollectionDataProviderInterface[] $dataProviders
     */
    public function __construct(iterable $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        foreach ($this->dataProviders as $dataProvider) {
            try {
                if ($dataProvider instanceof RestrictedDataProviderInterface
                    && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    continue;
                }

                return $dataProvider->getCollection($resourceClass, $operationName, $context);
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" in a data provider is deprecated in favor of implementing "%s"', ResourceClassNotSupportedException::class, RestrictedDataProviderInterface::class), \E_USER_DEPRECATED);
                continue;
            }
        }

        return [];
    }
}
