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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * {@inheritdoc}
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IdentifiersExtractor implements ContextAwareIdentifiersExtractorInterface
{
    use ResourceClassInfoTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $propertyAccessor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, PropertyAccessorInterface $propertyAccessor = null, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->resourceClassResolver = $resourceClassResolver;

        if (null === $this->resourceClassResolver) {
            @trigger_error(sprintf('Not injecting %s in the IdentifiersExtractor might introduce cache issues with object identifiers.', ResourceClassResolverInterface::class), \E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersFromResourceClass(string $resourceClass): array
    {
        $identifiers = [];
        foreach ($properties = $this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            if ($this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier() ?? false) {
                $identifiers[] = $property;
            }
        }

        if (!$identifiers) {
            if (\in_array('id', iterator_to_array($properties), true)) {
                return ['id'];
            }

            throw new RuntimeException(sprintf('No identifier defined in "%s". You should add #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]" on the property identifying the resource."', $resourceClass));
        }

        return $identifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersFromItem($item, array $context = []): array
    {
        $identifiers = [];
        $resourceClass = $this->getResourceClass($item, true);
        if (isset($context['identifiers'])) {
            foreach ($context['identifiers'] as $parameterName => [$class, $property]) {
                if ($item instanceof $class) {
                    $identifiers[$parameterName] = $this->propertyAccessor->getValue($item, $property);
                    continue;
                }

                foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
                    $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
                    $type = $propertyMetadata->getType();
                    if ($type && $type->getClassName() === $class) {
                        $identifiers[$parameterName] = $this->propertyAccessor->getValue($item, "$propertyName.$property");
                    }
                }
            }

            return $identifiers;
        }

        $identifierProperties = $this->getIdentifiersFromResourceClass($resourceClass);

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            if (!\in_array($propertyName, $identifierProperties, true)) {
                continue;
            }

            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            $identifier = $identifiers[$propertyName] = $this->propertyAccessor->getValue($item, $propertyName);

            if (!\is_object($identifier)) {
                continue;
            }

            if (null === $relatedResourceClass = $this->getResourceClass($identifier)) {
                continue;
            }

            $relatedItem = $identifier;
            unset($identifiers[$propertyName]);
            foreach ($this->propertyNameCollectionFactory->create($relatedResourceClass) as $relatedPropertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($relatedResourceClass, $relatedPropertyName);
                if ($propertyMetadata->isIdentifier()) {
                    if (isset($identifiers[$propertyName])) {
                        throw new RuntimeException(sprintf('Composite identifiers not supported in "%s" through relation "%s" of "%s" used as identifier', $relatedResourceClass, $propertyName, $resourceClass));
                    }

                    $identifiers[$propertyName] = $this->propertyAccessor->getValue($relatedItem, $relatedPropertyName);
                }
            }

            if (!isset($identifiers[$propertyName])) {
                throw new RuntimeException(sprintf('No identifier found in "%s" through relation "%s" of "%s" used as identifier', $relatedResourceClass, $propertyName, $resourceClass));
            }
        }

        return $identifiers;
    }
}
