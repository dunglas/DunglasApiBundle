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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Util\AnnotationFilterExtractorTrait;
use Doctrine\Common\Annotations\Reader;

/**
 * Adds filters to the resource metadata {@see ApiFilter} annotation.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AnnotationResourceFilterMetadataFactory implements ResourceMetadataFactoryInterface
{
    use AnnotationFilterExtractorTrait;

    private $reader;
    private $decorated;

    public function __construct(?Reader $reader = null, ResourceMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $parentResourceMetadata = null;
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        if (null === $parentResourceMetadata) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        $filters = array_keys($this->readFilterAnnotations($reflectionClass, $this->reader));

        if (!$filters) {
            return $parentResourceMetadata;
        }

        $parentFilters = $parentResourceMetadata->getAttribute('filters', []);

        if ($parentFilters) {
            $filters = array_merge($parentFilters, $filters);
        }

        $attributes = $parentResourceMetadata->getAttributes();

        if (!$attributes) {
            $attributes = [];
        }

        return $parentResourceMetadata->withAttributes(array_merge($attributes, ['filters' => $filters]));
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(?ResourceMetadata $parentPropertyMetadata, string $resourceClass): ResourceMetadata
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }
}
