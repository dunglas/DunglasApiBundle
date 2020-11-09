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
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a resource name collection from {@see ApiResource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $reader;
    private $paths;
    private $decorated;

    /**
     * @param string[] $paths
     */
    public function __construct(Reader $reader, array $paths, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ResourceNameCollection
    {
        $classes = [];

        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($this->paths) as $className => $reflectionClass) {
            $annotation = $this->reader->getClassAnnotation($reflectionClass, ApiResource::class);
            if ($annotation) {
                $classes[$className]['enabled'] = true;
                $classes[$className]['interface'] = $annotation->isInterface;
            }
        }

        \uasort($classes, static function (array $a, array $b) {
            return $b['interface'];
        });

        return new ResourceNameCollection(array_keys($classes));
    }
}
