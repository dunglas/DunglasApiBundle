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

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Util\ReflectionClassRecursiveIterator;
use ApiPlatform\Metadata\Resource;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a resource name collection from {@see ApiResource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationResourceNameCollectionFactory implements LegacyResourceNameCollectionFactoryInterface
{
    private $reader;
    private $paths;
    private $decorated;

    /**
     * @param string[] $paths
     */
    public function __construct(Reader $reader = null, array $paths, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(bool $legacy = true): ResourceNameCollection
    {
        if (true === $legacy) {
            @trigger_error(sprintf('Using a legacy %s is deprecated since 2.7 and will not be possible in 3.0.', __CLASS__), \E_USER_DEPRECATED);
        }

        $classes = [];

        if ($this->decorated) {
            foreach ($this->decorated instanceof LegacyResourceNameCollectionFactoryInterface ? $this->decorated->create($legacy) : $this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($this->paths) as $className => $reflectionClass) {
            if (\PHP_VERSION_ID >= 80000 && !$legacy && $reflectionClass->getAttributes(Resource::class)) {
                $classes[$className] = true;
                continue;
            }

            if (
                $legacy &&
                ((\PHP_VERSION_ID >= 80000 && ($reflectionClass->getAttributes(ApiResource::class))) ||
                (null !== $this->reader && $this->reader->getClassAnnotation($reflectionClass, ApiResource::class)))
            ) {
                $classes[$className] = true;
            }
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
