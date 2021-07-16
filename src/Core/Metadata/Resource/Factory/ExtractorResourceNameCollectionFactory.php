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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Extractor\ExtractorInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;

/**
 * Creates a resource name collection from {@see ApiResource} configuration files.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ExtractorResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $extractor;
    private $decorated;

    public function __construct(ExtractorInterface $extractor, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function create(): ResourceNameCollection
    {
        $classes = [];
        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach ($this->extractor->getResources() as $resourceClass => $resource) {
            $classes[$resourceClass] = true;
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
