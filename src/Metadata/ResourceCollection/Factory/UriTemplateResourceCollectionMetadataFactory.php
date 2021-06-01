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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class UriTemplateResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $pathSegmentNameGenerator;
    private $decorated;

    public function __construct(PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, ResourceCollectionMetadataFactoryInterface $decorated = null)
    {
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceMetadata = [];
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass)->getArrayCopy();
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        foreach ($parentResourceMetadata as $i => $resource) {
            if (!$resource->uriTemplate) {
                foreach ($resource->operations as $key => $operation) {
                    $operation->uriTemplate = $this->generateUriTemplate($operation);
                    // Change the operation key
                    unset($resource->operations[$key]);
                    $resource->operations[sprintf('_api_%s_%s', $operation->uriTemplate, strtolower($operation->method))] = $operation;
                }
            }

            $parentResourceMetadata[$i] = $resource;
        }

        return new ResourceCollection($parentResourceMetadata);
    }

    private function generateUriTemplate(Operation $operation): string
    {
        $uriTemplate = $operation->routePrefix ?: '';
        if (!$operation->identifiers) {
            return sprintf('%s/%s.{_format}', $uriTemplate, $this->pathSegmentNameGenerator->getSegmentName($operation->shortName));
        }

        $uriTemplate = sprintf('%s/%s', $uriTemplate, $this->pathSegmentNameGenerator->getSegmentName($operation->shortName));
        foreach (array_keys($operation->identifiers) as $parameterName) {
            $uriTemplate .= sprintf('/{%s}', $parameterName);
        }

        return sprintf('%s.{_format}', $uriTemplate);
    }
}
