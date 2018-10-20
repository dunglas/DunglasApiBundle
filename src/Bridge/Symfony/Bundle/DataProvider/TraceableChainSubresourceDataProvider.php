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
            $reflection = new \ReflectionProperty(ChainSubresourceDataProvider::class, 'dataProviders');
            $reflection->setAccessible(true);
            $this->dataProviders = $reflection->getValue($subresourceDataProvider);
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
        foreach ($this->dataProviders as $dataProvider) {
            $this->providersResponse[\get_class($dataProvider)] = null;
        }

        foreach ($this->dataProviders as $dataProvider) {
            try {
                if ($dataProvider instanceof RestrictedDataProviderInterface && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    $this->providersResponse[\get_class($dataProvider)] = false;
                    continue;
                }
                $this->providersResponse[\get_class($dataProvider)] = true;

                return $dataProvider->getSubresource($resourceClass, $identifiers, $context, $operationName);
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" in a data provider is deprecated in favor of implementing "%s"', ResourceClassNotSupportedException::class, RestrictedDataProviderInterface::class), E_USER_DEPRECATED);
                $this->providersResponse[\get_class($dataProvider)] = false;
                continue;
            }
        }

        return ($context['collection'] ?? false) ? [] : null;
    }
}
