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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\Resource;

/**
 * Transforms the given input/output metadata to a normalized one.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class InputOutputResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            $resourceMetadata->input = $this->transformInputOutput($resourceMetadata->input);
            $resourceMetadata->output = $this->transformInputOutput($resourceMetadata->output);

            if ($resourceMetadata->operations) {
                $resourceMetadata->operations = $this->getTransformedOperations($resourceMetadata->operations, $resourceMetadata);
            }

            // TODO: GraphQL operations as an object?
            // if ($graphQlAttributes = $resourceMetadata->graphQl) {
            //     $resourceMetadata->graphQl = $this->getTransformedOperations($resourceMetadata->graphQl, $resourceMetadata);
            // }

            $resourceMetadataCollection[$key] = $resourceMetadata;
        }

        return new ResourceCollection($resourceMetadataCollection);
    }

    private function getTransformedOperations(array $operations, Resource $resourceMetadata): array
    {
        foreach ($operations as $key => &$operation) {
            $operation->input = $operation->input ? $this->transformInputOutput($operation->input) : $resourceMetadata->input;
            $operation->output = $operation->output ? $this->transformInputOutput($operation->output) : $resourceMetadata->output;

            if (
                $operation->input
                && \array_key_exists('class', $operation->input)
                && null === $operation->input['class']
            ) {
                $operation->deserialize = $operation->deserialize ?? false;
                $operation->validate = $operation->validate ?? false;
            }

            if (
                $operation->output
                && \array_key_exists('class', $operation->output)
                && null === $operation->output['class']
            ) {
                $operation->status = $operation->status ?? 204;
            }
        }

        return $operations;
    }

    private function transformInputOutput($attribute): ?array
    {
        if (false === $attribute) {
            return ['class' => null];
        }

        if (!$attribute) {
            return null;
        }

        if (\is_string($attribute)) {
            $attribute = ['class' => $attribute];
        }

        if (!isset($attribute['name']) && isset($attribute['class'])) {
            $attribute['name'] = (new \ReflectionClass($attribute['class']))->getShortName();
        }

        return $attribute;
    }
}
