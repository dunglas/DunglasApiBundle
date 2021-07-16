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

use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * Extracts descriptions from PHPDoc.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @experimental
 */
final class PhpDocResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;
    private $docBlockFactory;
    private $contextFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated, DocBlockFactoryInterface $docBlockFactory = null)
    {
        $this->decorated = $decorated;
        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            if (null !== $resourceMetadata->getDescription()) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($resourceClass);

            try {
                $docBlock = $this->docBlockFactory->create($reflectionClass, $this->contextFactory->createFromReflector($reflectionClass));
                $resourceMetadata = $resourceMetadata->withDescription($docBlock->getSummary());
                $resourceMetadataCollection[$key] = $resourceMetadata;
            } catch (\InvalidArgumentException $e) {
                // Ignore empty DocBlocks
            }
        }

        return $resourceMetadataCollection;
    }
}
