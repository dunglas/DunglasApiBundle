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

namespace ApiPlatform\Core\Bridge\NelmioApiDoc\Extractor\AnnotationsProvider;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser;
use ApiPlatform\Core\Bridge\Symfony\Routing\OperationMethodResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\AnnotationsProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates Nelmio ApiDoc annotations for the api platform.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @deprecated since version 2.2, to be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
 */
final class ApiPlatformProvider implements AnnotationsProviderInterface
{
    use FilterLocatorTrait;

    private $resourceNameCollectionFactory;
    private $documentationNormalizer;
    private $resourceMetadataFactory;
    private $operationMethodResolver;

    /**
     * @param ContainerInterface|FilterCollection $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, NormalizerInterface $documentationNormalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, $filterLocator, OperationMethodResolverInterface $operationMethodResolver)
    {
        @trigger_error('The '.__CLASS__.' class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.', \E_USER_DEPRECATED);

        $this->setFilterLocator($filterLocator);

        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->documentationNormalizer = $documentationNormalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationMethodResolver = $operationMethodResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotations(): array
    {
        $resourceNameCollection = $this->resourceNameCollectionFactory->create();

        $hydraDoc = $this->documentationNormalizer->normalize(new Documentation($resourceNameCollection));
        if (!\is_array($hydraDoc)) {
            throw new \UnexpectedValueException('Expected data to be an array');
        }

        if (empty($hydraDoc)) {
            return [];
        }

        $entrypointHydraDoc = $this->getResourceHydraDoc($hydraDoc, '#Entrypoint');
        if (null === $entrypointHydraDoc) {
            return [];
        }

        $annotations = [];
        foreach ($resourceNameCollection as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $prefixedShortName = ($iri = $resourceMetadata->getIri()) ? $iri : '#'.$resourceMetadata->getShortName();
            $resourceHydraDoc = $this->getResourceHydraDoc($hydraDoc, $prefixedShortName);
            if (null === $resourceHydraDoc) {
                continue;
            }

            if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
                foreach ($collectionOperations as $operationName => $operation) {
                    $annotations[] = $this->getApiDoc(true, $resourceClass, $resourceMetadata, $operationName, $resourceHydraDoc, $entrypointHydraDoc);
                }
            }

            if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
                foreach ($itemOperations as $operationName => $operation) {
                    $annotations[] = $this->getApiDoc(false, $resourceClass, $resourceMetadata, $operationName, $resourceHydraDoc);
                }
            }
        }

        return $annotations;
    }

    /**
     * Builds ApiDoc annotation from ApiPlatform data.
     */
    private function getApiDoc(bool $collection, string $resourceClass, ResourceMetadata $resourceMetadata, string $operationName, array $resourceHydraDoc, array $entrypointHydraDoc = []): ApiDoc
    {
        if ($collection) {
            $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
            $route = $this->operationMethodResolver->getCollectionOperationRoute($resourceClass, $operationName);
            $operationHydraDoc = $this->getCollectionOperationHydraDoc($resourceMetadata->getShortName(), $method, $entrypointHydraDoc);
        } else {
            $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
            $route = $this->operationMethodResolver->getItemOperationRoute($resourceClass, $operationName);
            $operationHydraDoc = $this->getOperationHydraDoc($method, $resourceHydraDoc);
        }

        $data = [
            'resource' => $route->getPath(),
            'description' => $operationHydraDoc['hydra:title'] ?? '',
            'resourceDescription' => $resourceHydraDoc['hydra:title'] ?? '',
            'section' => $resourceHydraDoc['hydra:title'] ?? '',
        ];

        if (isset($operationHydraDoc['expects']) && 'owl:Nothing' !== $operationHydraDoc['expects']) {
            $data['input'] = sprintf('%s:%s:%s', ApiPlatformParser::IN_PREFIX, $resourceClass, $operationName);
        }

        if (isset($operationHydraDoc['returns']) && 'owl:Nothing' !== $operationHydraDoc['returns']) {
            $data['output'] = sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, $resourceClass, $operationName);
        }

        if ($collection && 'GET' === $method) {
            $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

            $data['filters'] = [];
            foreach ($resourceFilters as $filterId) {
                if ($filter = $this->getFilter($filterId)) {
                    foreach ($filter->getDescription($resourceClass) as $name => $definition) {
                        $data['filters'][] = ['name' => $name] + $definition;
                    }
                }
            }
        }

        $apiDoc = new ApiDoc($data);
        $apiDoc->setRoute($route);

        return $apiDoc;
    }

    /**
     * Gets Hydra documentation for the given resource.
     */
    private function getResourceHydraDoc(array $hydraApiDoc, string $prefixedShortName): ?array
    {
        if (!isset($hydraApiDoc['hydra:supportedClass']) || !\is_array($hydraApiDoc['hydra:supportedClass'])) {
            return null;
        }

        foreach ($hydraApiDoc['hydra:supportedClass'] as $supportedClass) {
            if (isset($supportedClass['@id']) && $supportedClass['@id'] === $prefixedShortName) {
                return $supportedClass;
            }
        }

        return null;
    }

    /**
     * Gets the Hydra documentation of a given operation.
     */
    private function getOperationHydraDoc(string $method, array $hydraDoc): array
    {
        if (!isset($hydraDoc['hydra:supportedOperation']) || !\is_array($hydraDoc['hydra:supportedOperation'])) {
            return [];
        }

        foreach ($hydraDoc['hydra:supportedOperation'] as $supportedOperation) {
            if ($supportedOperation['hydra:method'] === $method) {
                return $supportedOperation;
            }
        }

        return [];
    }

    /**
     * Gets the Hydra documentation for the collection operation.
     */
    private function getCollectionOperationHydraDoc(string $shortName, string $method, array $hydraEntrypointDoc): array
    {
        if (!isset($hydraEntrypointDoc['hydra:supportedProperty']) || !\is_array($hydraEntrypointDoc['hydra:supportedProperty'])) {
            return [];
        }

        $propertyName = '#Entrypoint/'.lcfirst($shortName);

        foreach ($hydraEntrypointDoc['hydra:supportedProperty'] as $supportedProperty) {
            if (isset($supportedProperty['hydra:property']['@id'])
                && $supportedProperty['hydra:property']['@id'] === $propertyName) {
                return $this->getOperationHydraDoc($method, $supportedProperty['hydra:property']);
            }
        }

        return [];
    }
}
