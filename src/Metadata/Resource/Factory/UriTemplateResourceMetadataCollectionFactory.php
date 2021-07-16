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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class UriTemplateResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $pathSegmentNameGenerator;
    private $decorated;

    public function __construct(PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            if ($resource->getUriTemplate()) {
                $resourceMetadataCollection[$i] = $resource->withExtraProperties($resource->getExtraProperties() + ['user_defined_uri_template' => true]);
            }

            $operations = iterator_to_array($resource->getOperations());

            foreach ($resource->getOperations() as $key => $operation) {
                if ($operation->getUriTemplate()) {
                    $operations[$key] = $operation->withExtraProperties($operation->getExtraProperties() + ['user_defined_uri_template' => true]);
                    continue;
                }

                if ($routeName = $operation->getRouteName()) {
                    unset($operations[$key]);
                    $operations[$routeName] = $operation;
                    continue;
                }

                $operation = $operation->withUriTemplate($this->generateUriTemplate($operation));
                // Change the operation key
                unset($operations[$key]);
                $operations[sprintf('_api_%s_%s%s', $operation->getUriTemplate(), strtolower($operation->getMethod()), $operation->isCollection() ? '_collection' : '')] = $operation;
            }

            $resource = $resource->withOperations($operations);
            $resourceMetadataCollection[$i] = $resource;
        }

        return new ResourceMetadataCollection($resourceMetadataCollection);
    }

    private function generateUriTemplate(Operation $operation): string
    {
        $uriTemplate = $operation->getRoutePrefix() ?: '';
        $uriTemplate = sprintf('%s/%s', $uriTemplate, $this->pathSegmentNameGenerator->getSegmentName($operation->getShortName()));

        if ($parameters = array_keys($operation->getIdentifiers())) {
            if ($operation->getCompositeIdentifier()) {
                $uriTemplate .= sprintf('/{%s}', $parameters[0]);
            } else {
                foreach ($parameters as $parameterName) {
                    $uriTemplate .= sprintf('/{%s}', $parameterName);
                }
            }
        }

        return sprintf('%s.{_format}', $uriTemplate);
    }
}
