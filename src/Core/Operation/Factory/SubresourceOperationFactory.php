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

namespace ApiPlatform\Core\Operation\Factory;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;

/**
 * @internal
 */
final class SubresourceOperationFactory implements SubresourceOperationFactoryInterface
{
    public const SUBRESOURCE_SUFFIX = '_subresource';
    public const FORMAT_SUFFIX = '.{_format}';
    public const ROUTE_OPTIONS = ['defaults' => [], 'requirements' => [], 'options' => [], 'host' => '', 'schemes' => [], 'condition' => '', 'controller' => null, 'stateless' => null];

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $pathSegmentNameGenerator;
    private $identifiersExtractor;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, IdentifiersExtractorInterface $identifiersExtractor = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
        $this->identifiersExtractor = $identifiersExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        $tree = [];
        $this->computeSubresourceOperations($resourceClass, $tree);

        return $tree;
    }

    /**
     * Handles subresource operations recursively and declare their corresponding routes.
     *
     * @param string $rootResourceClass null on the first iteration, it then keeps track of the origin resource class
     * @param array  $parentOperation   the previous call operation
     * @param int    $depth             the number of visited
     * @param int    $maxDepth
     */
    private function computeSubresourceOperations(string $resourceClass, array &$tree, string $rootResourceClass = null, array $parentOperation = null, array $visited = [], int $depth = 0, int $maxDepth = null): void
    {
        if (null === $rootResourceClass) {
            $rootResourceClass = $resourceClass;
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);

            if (!$subresource = $propertyMetadata->getSubresource()) {
                continue;
            }

            $subresourceClass = $subresource->getResourceClass();
            $subresourceMetadata = $this->resourceMetadataFactory->create($subresourceClass);
            $subresourceMetadata = $subresourceMetadata->withAttributes(($subresourceMetadata->getAttributes() ?: []) + ['identifiers' => !$this->identifiersExtractor ? [$property] : $this->identifiersExtractor->getIdentifiersFromResourceClass($subresourceClass)]);
            $isLastItem = ($parentOperation['resource_class'] ?? null) === $resourceClass && $propertyMetadata->isIdentifier();

            // A subresource that is also an identifier can't be a start point
            if ($isLastItem && (null === $parentOperation || false === $parentOperation['collection'])) {
                continue;
            }

            $visiting = "$resourceClass $property $subresourceClass";

            // Handle maxDepth
            if (null !== $maxDepth && $depth >= $maxDepth) {
                break;
            }
            if (isset($visited[$visiting])) {
                continue;
            }

            $rootResourceMetadata = $this->resourceMetadataFactory->create($rootResourceClass);
            $rootResourceMetadata = $rootResourceMetadata->withAttributes(($rootResourceMetadata->getAttributes() ?: []) + ['identifiers' => !$this->identifiersExtractor ? ['id'] : $this->identifiersExtractor->getIdentifiersFromResourceClass($rootResourceClass)]);
            $operationName = 'get';
            $operation = [
                'property' => $property,
                'collection' => $subresource->isCollection(),
                'resource_class' => $subresourceClass,
                'shortNames' => [$subresourceMetadata->getShortName()],
            ];

            if (null === $parentOperation) {
                $identifiers = (array) $rootResourceMetadata->getAttribute('identifiers');
                $rootShortname = $rootResourceMetadata->getShortName();
                $identifier = \is_string($key = array_key_first($identifiers)) ? $key : $identifiers[0];
                $operation['identifiers'][$identifier] = [$rootResourceClass, $identifiers[$identifier][1] ?? $identifier, true];
                $operation['operation_name'] = sprintf(
                    '%s_%s%s',
                    RouteNameGenerator::inflector($operation['property'], $operation['collection'] ?? false),
                    $operationName,
                    self::SUBRESOURCE_SUFFIX
                );

                $subresourceOperation = $rootResourceMetadata->getSubresourceOperations()[$operation['operation_name']] ?? [];

                $operation['route_name'] = sprintf(
                    '%s%s_%s',
                    RouteNameGenerator::ROUTE_NAME_PREFIX,
                    RouteNameGenerator::inflector($rootShortname),
                    $operation['operation_name']
                );

                $prefix = trim(trim($rootResourceMetadata->getAttribute('route_prefix', '')), '/');
                if ('' !== $prefix) {
                    $prefix .= '/';
                }

                $operation['path'] = $subresourceOperation['path'] ?? sprintf(
                    '/%s%s/{%s}/%s%s',
                    $prefix,
                    $this->pathSegmentNameGenerator->getSegmentName($rootShortname),
                    $identifier,
                    $this->pathSegmentNameGenerator->getSegmentName($operation['property'], $operation['collection']),
                    self::FORMAT_SUFFIX
                );

                if (!\in_array($rootShortname, $operation['shortNames'], true)) {
                    $operation['shortNames'][] = $rootShortname;
                }
            } else {
                $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
                $identifiers = (array) $resourceMetadata->getAttribute('identifiers', null === $this->identifiersExtractor ? ['id'] : $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass));
                $identifier = \is_string($key = array_key_first($identifiers)) ? $key : $identifiers[0];
                $operation['identifiers'] = $parentOperation['identifiers'];

                if (!isset($operation['identifiers'][$parentOperation['property']])) {
                    $operation['identifiers'][$parentOperation['property']] = [$resourceClass, $identifiers[$identifier][1] ?? $identifier, $isLastItem ? true : $parentOperation['collection']];
                }

                $operation['operation_name'] = str_replace(
                    'get'.self::SUBRESOURCE_SUFFIX,
                    RouteNameGenerator::inflector($isLastItem ? 'item' : $property, $operation['collection']).'_get'.self::SUBRESOURCE_SUFFIX,
                    $parentOperation['operation_name']
                );
                $operation['route_name'] = str_replace($parentOperation['operation_name'], $operation['operation_name'], $parentOperation['route_name']);

                if (!\in_array($resourceMetadata->getShortName(), $operation['shortNames'], true)) {
                    $operation['shortNames'][] = $resourceMetadata->getShortName();
                }

                $subresourceOperation = $rootResourceMetadata->getSubresourceOperations()[$operation['operation_name']] ?? [];

                if (isset($subresourceOperation['path'])) {
                    $operation['path'] = $subresourceOperation['path'];
                } else {
                    $operation['path'] = str_replace(self::FORMAT_SUFFIX, '', (string) $parentOperation['path']);

                    if ($parentOperation['collection']) {
                        $operation['path'] .= sprintf('/{%s}', array_key_last($operation['identifiers']));
                    }

                    if ($isLastItem) {
                        $operation['path'] .= self::FORMAT_SUFFIX;
                    } else {
                        $operation['path'] .= sprintf('/%s%s', $this->pathSegmentNameGenerator->getSegmentName($property, $operation['collection']), self::FORMAT_SUFFIX);
                    }
                }
            }

            if (isset($subresourceOperation['openapi_context'])) {
                $operation['openapi_context'] = $subresourceOperation['openapi_context'];
            }

            foreach (self::ROUTE_OPTIONS as $routeOption => $defaultValue) {
                $operation[$routeOption] = $subresourceOperation[$routeOption] ?? $defaultValue;
            }

            $tree[$operation['route_name']] = $operation;

            // Get the minimum maxDepth between the rootMaxDepth and the maxDepth of the to be visited Subresource
            $currentMaxDepth = array_filter([$maxDepth, $subresource->getMaxDepth()], 'is_int');
            $currentMaxDepth = empty($currentMaxDepth) ? null : min($currentMaxDepth);

            $this->computeSubresourceOperations($subresourceClass, $tree, $rootResourceClass, $operation, $visited + [$visiting => true], $depth + 1, $currentMaxDepth);
        }
    }
}
