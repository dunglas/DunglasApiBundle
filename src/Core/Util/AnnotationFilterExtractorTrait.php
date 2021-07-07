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

namespace ApiPlatform\Core\Util;

use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Annotations\Reader;

/**
 * Generates a service id for a generic filter.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait AnnotationFilterExtractorTrait
{
    /**
     * Filters annotations to get back only ApiFilter annotations.
     *
     * @param \ReflectionClass|\ReflectionProperty $reflector
     *
     * @return \Iterator only ApiFilter annotations
     */
    private function getFilterAnnotations(\Reflector $reflector, ?Reader $reader = null): \Iterator
    {
        if (\PHP_VERSION_ID >= 80000 && $attributes = $reflector->getAttributes(ApiFilter::class)) {
            foreach ($attributes as $attribute) {
                yield $attribute->newInstance();
            }
        }

        if (null === $reader) {
            return;
        }

        $miscAnnotations = $reflector instanceof \ReflectionClass ? $reader->getClassAnnotations($reflector) : $reader->getPropertyAnnotations($reflector);
        foreach ($miscAnnotations as $miscAnnotation) {
            if (ApiFilter::class === \get_class($miscAnnotation)) {
                yield $miscAnnotation;
            }
        }
    }

    /**
     * Given a filter annotation and reflection elements, find out the properties where the filter is applied.
     */
    private function getFilterProperties(ApiFilter $filterAnnotation, \ReflectionClass $reflectionClass, \ReflectionProperty $reflectionProperty = null): array
    {
        $properties = [];

        if ($filterAnnotation->properties) {
            foreach ($filterAnnotation->properties as $property => $strategy) {
                if (\is_int($property)) {
                    $properties[$strategy] = null;
                } else {
                    $properties[$property] = $strategy;
                }
            }

            return $properties;
        }

        if (null !== $reflectionProperty) {
            $properties[$reflectionProperty->getName()] = $filterAnnotation->strategy ?: null;

            return $properties;
        }

        if ($filterAnnotation->strategy) {
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $properties[$reflectionProperty->getName()] = $filterAnnotation->strategy;
            }
        }

        return $properties;
    }

    /**
     * Reads filter annotations from a ReflectionClass.
     *
     * @return array Key is the filter id. It has two values, properties and the ApiFilter instance
     */
    private function readFilterAnnotations(\ReflectionClass $reflectionClass, Reader $reader = null): array
    {
        $filters = [];

        foreach ($this->getFilterAnnotations($reflectionClass, $reader) as $filterAnnotation) {
            $filterClass = $filterAnnotation->filterClass;
            $id = $this->generateFilterId($reflectionClass, $filterClass, $filterAnnotation->id);

            if (!isset($filters[$id])) {
                $filters[$id] = [$filterAnnotation->arguments, $filterClass];
            }

            if ($properties = $this->getFilterProperties($filterAnnotation, $reflectionClass)) {
                $filters[$id][0]['properties'] = $properties;
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            foreach ($this->getFilterAnnotations($reflectionProperty, $reader) as $filterAnnotation) {
                $filterClass = $filterAnnotation->filterClass;
                $id = $this->generateFilterId($reflectionClass, $filterClass, $filterAnnotation->id);

                if (!isset($filters[$id])) {
                    $filters[$id] = [$filterAnnotation->arguments, $filterClass];
                }

                if ($properties = $this->getFilterProperties($filterAnnotation, $reflectionClass, $reflectionProperty)) {
                    if (isset($filters[$id][0]['properties'])) {
                        $filters[$id][0]['properties'] = array_merge($filters[$id][0]['properties'], $properties);
                    } else {
                        $filters[$id][0]['properties'] = $properties;
                    }
                }
            }
        }

        $parent = $reflectionClass->getParentClass();

        if (false !== $parent) {
            return array_merge($filters, $this->readFilterAnnotations($parent, $reader));
        }

        return $filters;
    }

    /**
     * Generates a unique, per-class and per-filter identifier prefixed by `annotated_`.
     *
     * @param \ReflectionClass $reflectionClass the reflection class of a Resource
     * @param string           $filterClass     the filter class
     * @param string|null      $filterId        the filter id
     */
    private function generateFilterId(\ReflectionClass $reflectionClass, string $filterClass, string $filterId = null): string
    {
        $suffix = null !== $filterId ? '_'.$filterId : $filterId;

        return 'annotated_'.Inflector::tableize(str_replace('\\', '', $reflectionClass->getName().(new \ReflectionClass($filterClass))->getName().$suffix));
    }
}
