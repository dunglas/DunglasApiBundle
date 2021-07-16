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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

trait InputOutputMetadataTrait
{
    /**
     * @param ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
     */
    protected $resourceMetadataFactory;

    protected function getInputClass(string $class, array $context = []): ?string
    {
        if (!$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            return $this->getInputOutputMetadata($class, 'input', $context);
        }

        $operation = $context['operation'] ?? null;
        if (!$operation && $this->resourceMetadataFactory) {
            try {
                $operation = $this->resourceMetadataFactory->create($class)->getOperation();
            } catch (OperationNotFoundException $e) {
                return null;
            }
        }

        return $operation ? $operation->getInput()['class'] ?? null : null;
    }

    protected function getOutputClass(string $class, array $context = []): ?string
    {
        if (!$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            return $this->getInputOutputMetadata($class, 'output', $context);
        }

        $operation = $context['operation'] ?? null;
        if (!$operation && $this->resourceMetadataFactory) {
            try {
                $operation = $this->resourceMetadataFactory->create($class)->getOperation();
            } catch (OperationNotFoundException $e) {
                return null;
            }
        }

        return $operation ? $operation->getOutput()['class'] ?? null : null;
    }

    // TODO: remove in 3.0
    private function getInputOutputMetadata(string $class, string $inputOrOutput, array $context)
    {
        if (null !== ($context[$inputOrOutput]['class'] ?? null)) {
            return $context[$inputOrOutput]['class'] ?? null;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            try {
                $metadata = $this->resourceMetadataFactory->create($class);
            } catch (ResourceClassNotFoundException $e) {
                return null;
            }

            return $metadata->getAttribute($inputOrOutput)['class'] ?? null;
        }

        // note we should always go through the context above this is not right
        $metadata = $this->resourceMetadataFactory->create($class);

        return \count($metadata) ? $metadata[0]->getInput()['class'] ?? null : null;
    }
}
