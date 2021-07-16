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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Normalizes enabled formats.
 *
 * Formats hierarchy:
 * * resource formats
 *   * resource input/output formats
 *     * operation formats
 *       * operation input/output formats
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @experimental
 */
final class FormatsResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;
    private $formats;
    private $patchFormats;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated, array $formats, array $patchFormats)
    {
        $this->decorated = $decorated;
        $this->formats = $formats;
        $this->patchFormats = $patchFormats;
    }

    /**
     * Adds the formats attributes.
     *
     * @see OperationResourceMetadataFactory
     *
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $index => $resourceMetadata) {
            $rawResourceFormats = $resourceMetadata->getFormats();
            $resourceFormats = null === $rawResourceFormats ? $this->formats : $this->normalizeFormats($rawResourceFormats);
            $resourceInputFormats = $resourceMetadata->getInputFormats() ? $this->normalizeFormats($resourceMetadata->getInputFormats()) : $resourceFormats;
            $resourceOutputFormats = $resourceMetadata->getOutputFormats() ? $this->normalizeFormats($resourceMetadata->getOutputFormats()) : $resourceFormats;

            $resourceMetadataCollection[$index] = $resourceMetadataCollection[$index]->withOperations($this->normalize($resourceInputFormats, $resourceOutputFormats, iterator_to_array($resourceMetadata->getOperations())));
        }

        return $resourceMetadataCollection;
    }

    private function normalize(array $resourceInputFormats, array $resourceOutputFormats, array $operations): array
    {
        $newOperations = [];
        $patchFormats = null;
        foreach ($operations as $operationName => $operation) {
            if (($isPatch = Operation::METHOD_PATCH === $operation->getMethod()) && !$operation->getFormats() && !$operation->getInputFormats()) {
                $operation->withInputFormats($this->patchFormats);
            }

            if ($operation->getFormats()) {
                $operation->withFormats($this->normalizeFormats($operation->getFormats()));
            }

            $operation = $operation->withInputFormats($operation->getInputFormats() ? $this->normalizeFormats($operation->getInputFormats()) : $operation->getFormats() ?? $resourceInputFormats);
            $operation = $operation->withOutputFormats($operation->getOutputFormats() ? $this->normalizeFormats($operation->getOutputFormats()) : $operation->getFormats() ?? $resourceOutputFormats);

            if ($isPatch) {
                $patchFormats = $operation->getInputFormats();
            }

            $newOperations[$operationName] = $operation;
        }

        if (!$patchFormats) {
            return $newOperations;
        }

        // Prepare an Accept-Patch header
        foreach ($operations as $operationName => $operation) {
            if ($operation->isCollection()) {
                continue;
            }

            $patchMimeTypes = [];

            foreach ($patchFormats as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $patchMimeTypes[] = $mimeType;
                }
            }

            $newOperations[$operationName] = $operation->withAcceptPatch(implode(', ', $patchMimeTypes));
        }

        return $newOperations;
    }

    /**
     * @param array|string $currentFormats
     *
     * @throws InvalidArgumentException
     */
    private function normalizeFormats($currentFormats): array
    {
        $currentFormats = (array) $currentFormats;

        $normalizedFormats = [];
        foreach ($currentFormats as $format => $value) {
            if (!is_numeric($format)) {
                $normalizedFormats[$format] = (array) $value;
                continue;
            }
            if (!\is_string($value)) {
                throw new InvalidArgumentException(sprintf("The 'formats' attributes value must be a string when trying to include an already configured format, %s given.", \gettype($value)));
            }
            if (\array_key_exists($value, $this->formats)) {
                $normalizedFormats[$value] = $this->formats[$value];
                continue;
            }

            throw new InvalidArgumentException(sprintf("You either need to add the format '%s' to your project configuration or declare a mime type for it in your annotation.", $value));
        }

        return $normalizedFormats;
    }
}
