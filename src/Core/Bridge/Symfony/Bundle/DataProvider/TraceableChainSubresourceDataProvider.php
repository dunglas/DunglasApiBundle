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

use ApiPlatform\Core\DataProvider\ChainSubresourceDataProvider;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class TraceableChainSubresourceDataProvider implements SubresourceDataProviderInterface
{
    private $dataProviders = [];
    private $context = [];
    private $providersResponse = [];

    public function __construct(SubresourceDataProviderInterface $subresourceDataProvider)
    {
        if ($subresourceDataProvider instanceof ChainSubresourceDataProvider) {
            $this->dataProviders = $subresourceDataProvider->dataProviders;
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

    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
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
                if ($dataProvider instanceof RestrictedDataProviderInterface && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    continue;
                }

                $result = $dataProvider->getSubresource($resourceClass, $identifiers, $context, $operationName);
                $this->providersResponse[\get_class($dataProvider)] = $match = true;
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" in a data provider is deprecated in favor of implementing "%s"', ResourceClassNotSupportedException::class, RestrictedDataProviderInterface::class), \E_USER_DEPRECATED);
                continue;
            }
        }

        if ($match) {
            return $result;
        }

        return ($context['collection'] ?? false) ? [] : null;
    }
}
