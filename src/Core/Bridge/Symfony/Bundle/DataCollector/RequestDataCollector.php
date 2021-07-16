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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DataCollector;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataPersister\TraceableChainDataPersister;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainCollectionDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainItemDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainSubresourceDataProvider;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use PackageVersions\Versions;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @author Julien DENIAU <julien.deniau@gmail.com>
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class RequestDataCollector extends DataCollector
{
    private $metadataFactory;
    private $filterLocator;
    private $collectionDataProvider;
    private $itemDataProvider;
    private $subresourceDataProvider;
    private $dataPersister;

    public function __construct(ResourceMetadataFactoryInterface $metadataFactory, ContainerInterface $filterLocator, CollectionDataProviderInterface $collectionDataProvider = null, ItemDataProviderInterface $itemDataProvider = null, SubresourceDataProviderInterface $subresourceDataProvider = null, DataPersisterInterface $dataPersister = null)
    {
        $this->metadataFactory = $metadataFactory;
        $this->filterLocator = $filterLocator;
        $this->collectionDataProvider = $collectionDataProvider;
        $this->itemDataProvider = $itemDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->dataPersister = $dataPersister;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $counters = ['ignored_filters' => 0];
        $resourceClass = $request->attributes->get('_api_resource_class');
        $resourceMetadata = $resourceClass ? $this->metadataFactory->create($resourceClass) : null;

        $filters = [];
        foreach ($resourceMetadata ? $resourceMetadata->getAttribute('filters', []) : [] as $id) {
            if ($this->filterLocator->has($id)) {
                $filters[$id] = \get_class($this->filterLocator->get($id));
                continue;
            }

            $filters[$id] = null;
            ++$counters['ignored_filters'];
        }

        $requestAttributes = RequestAttributesExtractor::extractAttributes($request);
        if (isset($requestAttributes['previous_data'])) {
            $requestAttributes['previous_data'] = $this->cloneVar($requestAttributes['previous_data']);
        }

        $this->data = [
            'resource_class' => $resourceClass,
            'resource_metadata' => $resourceMetadata ? $this->cloneVar($resourceMetadata) : null,
            'acceptable_content_types' => $request->getAcceptableContentTypes(),
            'filters' => $filters,
            'counters' => $counters,
            'dataProviders' => [],
            'dataPersisters' => ['responses' => []],
            'request_attributes' => $requestAttributes,
        ];

        if ($this->collectionDataProvider instanceof TraceableChainCollectionDataProvider) {
            $this->data['dataProviders']['collection'] = [
                'context' => $this->cloneVar($this->collectionDataProvider->getContext()),
                'responses' => $this->collectionDataProvider->getProvidersResponse(),
            ];
        }

        if ($this->itemDataProvider instanceof TraceableChainItemDataProvider) {
            $this->data['dataProviders']['item'] = [
                'context' => $this->cloneVar($this->itemDataProvider->getContext()),
                'responses' => $this->itemDataProvider->getProvidersResponse(),
            ];
        }

        if ($this->subresourceDataProvider instanceof TraceableChainSubresourceDataProvider) {
            $this->data['dataProviders']['subresource'] = [
                'context' => $this->cloneVar($this->subresourceDataProvider->getContext()),
                'responses' => $this->subresourceDataProvider->getProvidersResponse(),
            ];
        }

        if ($this->dataPersister instanceof TraceableChainDataPersister) {
            $this->data['dataPersisters']['responses'] = $this->dataPersister->getPersistersResponse();
        }
    }

    public function getAcceptableContentTypes(): array
    {
        return $this->data['acceptable_content_types'] ?? [];
    }

    public function getResourceClass()
    {
        return $this->data['resource_class'] ?? null;
    }

    public function getResourceMetadata()
    {
        return $this->data['resource_metadata'] ?? null;
    }

    public function getRequestAttributes(): array
    {
        return $this->data['request_attributes'] ?? [];
    }

    public function getFilters(): array
    {
        return $this->data['filters'] ?? [];
    }

    public function getCounters(): array
    {
        return $this->data['counters'] ?? [];
    }

    public function getCollectionDataProviders(): array
    {
        return $this->data['dataProviders']['collection'] ?? ['context' => [], 'responses' => []];
    }

    public function getItemDataProviders(): array
    {
        return $this->data['dataProviders']['item'] ?? ['context' => [], 'responses' => []];
    }

    public function getSubresourceDataProviders(): array
    {
        return $this->data['dataProviders']['subresource'] ?? ['context' => [], 'responses' => []];
    }

    public function getDataPersisters(): array
    {
        return $this->data['dataPersisters'] ?? ['responses' => []];
    }

    public function getVersion(): ?string
    {
        if (!class_exists(Versions::class)) {
            return null;
        }

        $version = Versions::getVersion('api-platform/core');
        preg_match('/^v(.*?)@/', $version, $output);

        return $output[1] ?? strtok($version, '@');
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'api_platform.data_collector.request';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }
}
