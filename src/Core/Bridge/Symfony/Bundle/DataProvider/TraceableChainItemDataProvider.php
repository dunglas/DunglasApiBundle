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

use ApiPlatform\Core\DataProvider\ChainItemDataProvider;
use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictDataProviderTrait;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class TraceableChainItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    use RestrictDataProviderTrait;

    private $context = [];
    private $providersResponse = [];

    public function __construct(ItemDataProviderInterface $itemDataProvider)
    {
        if ($itemDataProvider instanceof ChainItemDataProvider) {
            $this->dataProviders = $itemDataProvider->dataProviders;
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

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $this->context = $context;
        $match = false;
        $result = null;

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

                $identifier = $id;
                if (!$dataProvider instanceof DenormalizedIdentifiersAwareItemDataProviderInterface && $identifier && \is_array($identifier)) {
                    if (\count($identifier) > 1) {
                        @trigger_error(sprintf('Receiving "$id" as non-array in an item data provider is deprecated in 2.3 in favor of implementing "%s".', DenormalizedIdentifiersAwareItemDataProviderInterface::class), \E_USER_DEPRECATED);
                        $identifier = http_build_query($identifier, '', ';');
                    } else {
                        $identifier = current($identifier);
                    }
                }

                $result = $dataProvider->getItem($resourceClass, $identifier, $operationName, $context);
                $this->providersResponse[\get_class($dataProvider)] = $match = true;
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" is deprecated in favor of implementing "%s"', \get_class($e), RestrictedDataProviderInterface::class), \E_USER_DEPRECATED);
                continue;
            }
        }

        return $result;
    }
}
