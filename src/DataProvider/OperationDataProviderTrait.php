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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Identifier\ContextAwareIdentifierConverterInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;

/**
 * @internal
 */
trait OperationDataProviderTrait
{
    /**
     * @var CollectionDataProviderInterface
     */
    private $collectionDataProvider;

    /**
     * @var ItemDataProviderInterface
     */
    private $itemDataProvider;

    /**
     * @var SubresourceDataProviderInterface|null
     */
    private $subresourceDataProvider;

    /**
     * @var IdentifierConverterInterface|null
     */
    private $identifierConverter;

    /**
     * Retrieves data for a collection operation.
     *
     * @return iterable
     */
    private function getCollectionData(array $attributes, array $context)
    {
        return $this->collectionDataProvider->getCollection($attributes['resource_class'], $attributes['operation_name'] ?? $attributes['collection_operation_name'], $context);
    }

    /**
     * Gets data for an item operation.
     *
     * @return object|null
     */
    private function getItemData($identifiers, array $attributes, array $context)
    {
        return $this->itemDataProvider->getItem($attributes['resource_class'], $identifiers, $attributes['operation_name'] ?? $attributes['item_operation_name'], $context);
    }

    /**
     * Gets data for a nested operation.
     *
     * @throws RuntimeException
     *
     * @return array|object|null
     */
    private function getSubresourceData($identifiers, array $attributes, array $context)
    {
        if (null === $this->subresourceDataProvider) {
            throw new RuntimeException('Subresources not supported');
        }

        // TODO: SubresourceDataProvider wants: ['id' => ['id' => 1], 'relatedDummies' => ['id' => 2]], identifiers is ['id' => 1, 'relatedDummies' => 2]
        $subresourceIdentifiers = [];
        foreach ($attributes['identifiers'] as $parameterName => [$class, $property]) {
            if (false !== ($attributes['identifiers'][$parameterName][2] ?? null)) {
                $subresourceIdentifiers[$parameterName] = [$property => $identifiers[$parameterName]];
            }
        }

        return $this->subresourceDataProvider->getSubresource($attributes['resource_class'], $subresourceIdentifiers, $attributes['subresource_context'] + $context, $attributes['subresource_operation_name']);
    }

    /**
     * @param array $parameters - usually comes from $request->attributes->all()
     *
     * @throws InvalidIdentifierException
     */
    private function extractIdentifiers(array $parameters, array $attributes)
    {
        $identifiersKeys = $attributes['identifiers'] ?? ['id' => [$attributes['resource_class'], 'id']];
        $identifiers = [];

        $identifiersNumber = \count($identifiersKeys);
        foreach ($identifiersKeys as $parameterName => $identifiedBy) {
            if (!isset($parameters[$parameterName])) {
                if ($attributes['has_composite_identifier']) {
                    $identifiers = CompositeIdentifierParser::parse($parameters['id']);
                    if (($currentIdentifiersNumber = \count($identifiers)) !== $identifiersNumber) {
                        throw new InvalidIdentifierException(sprintf('Expected %d identifiers, got %d', $identifiersNumber, $currentIdentifiersNumber));
                    }

                    return $this->identifierConverter->convert($identifiers, $identifiedBy[0]);
                }

                // TODO: Subresources tuple may have a third item representing if it is a "collection", this behavior will be removed in 3.0
                if (false === ($identifiedBy[2] ?? null)) {
                    continue;
                }

                throw new InvalidIdentifierException(sprintf('Parameter "%s" not found', $parameterName));
            }

            $identifiers[$parameterName] = $parameters[$parameterName];
        }

        if ($this->identifierConverter instanceof ContextAwareIdentifierConverterInterface) {
            return $this->identifierConverter->convert($identifiers, $attributes['resource_class'], ['identifiers' => $identifiersKeys]);
        }

        return $this->identifierConverter->convert($identifiers, $attributes['resource_class']);
    }
}
