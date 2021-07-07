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

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * Extracts descriptions from PHPDoc.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PhpDocResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;
    private $docBlockFactory;
    private $contextFactory;

    public function __construct(ResourceMetadataFactoryInterface $decorated, DocBlockFactoryInterface $docBlockFactory = null)
    {
        $this->decorated = $decorated;
        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        if (null !== $resourceMetadata->getDescription()) {
            return $resourceMetadata;
        }

        $reflectionClass = new \ReflectionClass($resourceClass);

        try {
            $docBlock = $this->docBlockFactory->create($reflectionClass, $this->contextFactory->createFromReflector($reflectionClass));
            $resourceMetadata = $resourceMetadata->withDescription($docBlock->getSummary());
        } catch (\InvalidArgumentException $e) {
            // Ignore empty DocBlocks
        }

        return $resourceMetadata;
    }
}
