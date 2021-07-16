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

namespace ApiPlatform\Api;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * {@inheritdoc}
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IdentifiersExtractor implements IdentifiersExtractorInterface
{
    use ResourceClassInfoTrait;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     *
     * TODO: 3.0 identifiers should be stringable?
     */
    public function getIdentifiersFromItem($item, string $operationName = null, array $context = []): array
    {
        $identifiers = [];
        $resourceClass = $this->getResourceClass($item, true);
        $operation = $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName);

        foreach ($operation->getIdentifiers() as $parameterName => [$class, $property]) {
            $identifierValue = $this->resolveIdentifierValue($item, $class, $property);

            if (!$identifierValue) {
                throw new RuntimeException('No identifier value found, did you forgot to persist the entity?');
            }

            if (is_scalar($identifierValue) || $identifierValue instanceof \Stringable) {
                $identifiers[$parameterName] = (string) $identifierValue;
                continue;
            }

            // we could recurse to find correct identifiers until there it is a scalar but this is not really supported and adds a lot of complexity
            // instead we're deprecating this behavior in favor of something that can be transformed to a string
            if ($this->isResourceClass($relatedResourceClass = $this->getObjectClass($identifierValue))) {
                trigger_deprecation('api-platform/core', '2.7', 'Using a resource class as identifier is deprecated, please make this identifier Stringable');
                $relatedOperation = $this->resourceMetadataFactory->create($relatedResourceClass)->getOperation();
                $relatedIdentifiers = $relatedOperation->getIdentifiers();
                if (1 === \count($relatedIdentifiers)) {
                    $identifierValue = $this->resolveIdentifierValue($identifierValue, $relatedResourceClass, current($relatedIdentifiers)[1]);

                    if (is_scalar($identifierValue) || $identifierValue instanceof \Stringable) {
                        $identifiers[$parameterName] = (string) $identifierValue;
                        continue;
                    }
                }
            }

            throw new RuntimeException(sprintf('We were not able to resolve the identifier matching parameter "%s".', $parameterName));
        }

        return $identifiers;
    }

    private function resolveIdentifierValue($item, string $class, string $property)
    {
        if ($item instanceof $class) {
            return $this->propertyAccessor->getValue($item, $property);
        }

        $resourceClass = $this->getResourceClass($item, true);
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            $type = $propertyMetadata->getType();
            if (!$type) {
                continue;
            }

            if ($type->getClassName() === $class) {
                return $this->propertyAccessor->getValue($item, "$propertyName.$property");
            }

            if ($type->isCollection() && $type->getCollectionValueType()->getClassName() === $class) {
                return $this->propertyAccessor->getValue($item, sprintf('%s[0].%s', $propertyName, $property));
            }
        }

        throw new \RuntimeException('Not able to retrieve identifiers.');
    }
}
