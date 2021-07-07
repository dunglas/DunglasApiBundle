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
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

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
 */
final class FormatsResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;
    private $formats;
    private $patchFormats;

    public function __construct(ResourceMetadataFactoryInterface $decorated, array $formats, array $patchFormats)
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
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);
        $rawResourceFormats = $resourceMetadata->getAttribute('formats');
        $resourceFormats = null === $rawResourceFormats ? $this->formats : $this->normalizeFormats($rawResourceFormats);

        $rawResourceInputFormats = $resourceMetadata->getAttribute('input_formats');
        $rawResourceOutputFormats = $resourceMetadata->getAttribute('output_formats');

        $resourceInputFormats = $rawResourceInputFormats ? $this->normalizeFormats($rawResourceInputFormats) : $resourceFormats;
        $resourceOutputFormats = $rawResourceOutputFormats ? $this->normalizeFormats($rawResourceOutputFormats) : $resourceFormats;

        if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
            $resourceMetadata = $resourceMetadata->withCollectionOperations($this->normalize($resourceInputFormats, $resourceOutputFormats, $collectionOperations));
        }

        if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
            $resourceMetadata = $resourceMetadata->withItemOperations($this->normalize($resourceInputFormats, $resourceOutputFormats, $itemOperations));
        }

        if (null !== $subresourceOperations = $resourceMetadata->getSubresourceOperations()) {
            $resourceMetadata = $resourceMetadata->withSubresourceOperations($this->normalize($resourceInputFormats, $resourceOutputFormats, $subresourceOperations));
        }

        return $resourceMetadata;
    }

    private function normalize(array $resourceInputFormats, array $resourceOutputFormats, array $operations): array
    {
        $newOperations = [];
        foreach ($operations as $operationName => $operation) {
            if ('PATCH' === ($operation['method'] ?? '') && !isset($operation['formats']) && !isset($operation['input_formats'])) {
                $operation['input_formats'] = $this->patchFormats;
            }

            if (isset($operation['formats'])) {
                $operation['formats'] = $this->normalizeFormats($operation['formats']);
            }

            $operation['input_formats'] = isset($operation['input_formats']) ? $this->normalizeFormats($operation['input_formats']) : $operation['formats'] ?? $resourceInputFormats;
            $operation['output_formats'] = isset($operation['output_formats']) ? $this->normalizeFormats($operation['output_formats']) : $operation['formats'] ?? $resourceOutputFormats;

            $newOperations[$operationName] = $operation;
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
