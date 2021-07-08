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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Util;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

trait PropertyNameNormalizerTrait
{
    abstract protected function getNameConverter(): ?NameConverterInterface;

    abstract protected function getClassMetadata(string $resourceClass): ClassMetadata;

    /**
     * @param string $property
     *
     * @return string
     */
    protected function denormalizePropertyName($property/*, ?string $resourceClass = null, array $context = []*/)
    {
        if (\func_num_args() > 1) {
            $resourceClass = null === ($arg = func_get_arg(1)) ? $arg : (string) $arg;
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.7.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }

            $resourceClass = null;
        }

        if (\func_num_args() > 2) {
            $context = (array) func_get_arg(2);
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a third `$context` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.7.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }

            $context = [];
        }

        if (!$nameConverter = $this->getNameConverter()) {
            return $property;
        }

        $denormalizedProperties = [];
        foreach (explode('.', (string) $property) as $subProperty) {
            if ($nameConverter instanceof AdvancedNameConverterInterface) {
                $denormalizedProperty = $nameConverter->denormalize($subProperty, $resourceClass, null, $context);
            } else {
                $denormalizedProperty = $nameConverter->denormalize($subProperty);
            }

            if (null !== $resourceClass && ($doctrineClassMetadata = $this->getClassMetadata($resourceClass))->hasAssociation($denormalizedProperty)) {
                $resourceClass = $doctrineClassMetadata->getAssociationTargetClass($denormalizedProperty);
            } else {
                $resourceClass = null;
            }

            $denormalizedProperties[] = $denormalizedProperty;
        }

        return implode('.', $denormalizedProperties);
    }

    /**
     * @param string $property
     *
     * @return string
     */
    protected function normalizePropertyName($property/*, ?string $resourceClass = null, array $context = []*/)
    {
        if (\func_num_args() > 1) {
            $resourceClass = null === ($arg = func_get_arg(1)) ? $arg : (string) $arg;
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.7.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }

            $resourceClass = null;
        }

        if (\func_num_args() > 2) {
            $context = (array) func_get_arg(2);
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a third `$context` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.7.', __FUNCTION__), \E_USER_DEPRECATED);
                }
            }

            $context = [];
        }

        if (!$nameConverter = $this->getNameConverter()) {
            return $property;
        }

        $normalizedProperties = [];
        foreach (explode('.', (string) $property) as $subProperty) {
            if ($nameConverter instanceof AdvancedNameConverterInterface) {
                $normalizedProperty = $nameConverter->normalize($subProperty, $resourceClass, null, $context);
            } else {
                $normalizedProperty = $nameConverter->normalize($subProperty);
            }

            if (null !== $resourceClass && ($doctrineClassMetadata = $this->getClassMetadata($resourceClass))->hasAssociation($subProperty)) {
                $resourceClass = $doctrineClassMetadata->getAssociationTargetClass($subProperty);
            } else {
                $resourceClass = null;
            }

            $normalizedProperties[] = $normalizedProperty;
        }

        return implode('.', $normalizedProperties);
    }
}
