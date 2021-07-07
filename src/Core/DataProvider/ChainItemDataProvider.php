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
final class ChainItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    use RestrictDataProviderTrait;

    /**
     * @param ItemDataProviderInterface[] $dataProviders
     */
    public function __construct(iterable $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        foreach ($this->dataProviders as $dataProvider) {
            try {
                if ($dataProvider instanceof RestrictedDataProviderInterface
                    && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    continue;
                }

                $identifier = $id;
                if (!$dataProvider instanceof DenormalizedIdentifiersAwareItemDataProviderInterface && $identifier && \is_array($identifier)) {
                    if (\count($identifier) > 1) {
                        @trigger_error(sprintf('Receiving "$id" as non-array in an item data provider is deprecated in 2.3 in favor of implementing "%s".', DenormalizedIdentifiersAwareItemDataProviderInterface::class), \E_USER_DEPRECATED);
                        $identifier = http_build_query($identifier, '', ';');
                    } else {
                        $identifier = current($identifier);
                    }
                }

                return $dataProvider->getItem($resourceClass, $identifier, $operationName, $context);
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" is deprecated in favor of implementing "%s"', \get_class($e), RestrictedDataProviderInterface::class), \E_USER_DEPRECATED);
                continue;
            }
        }

        return null;
    }
}
