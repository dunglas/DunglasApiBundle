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

use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Util\ReflectionClassRecursiveIterator;

class DirectoryResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $paths;
    private $decorated;

    public function __construct(
        array $paths,
        ResourceNameCollectionFactoryInterface $decorated = null
    ) {
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    public function create(): ResourceNameCollection
    {
        $classes = [];
        if (null !== $this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($this->paths) as $className => $reflectionClass) {
            if (interface_exists($className)) {
                continue;
            }
            $classes[$className] = true;
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
