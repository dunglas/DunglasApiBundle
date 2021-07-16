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

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\CacheKeyTrait;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;

/**
 * Converts between objects and array including HAL metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use CacheKeyTrait;
    use ClassInfoTrait;
    use ContextTrait;

    public const FORMAT = 'jsonhal';

    private $componentsCache = [];
    private $attributesMetadataCache = [];

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (null !== $this->getOutputClass($this->getObjectClass($object), $context)) {
            return parent::normalize($object, $format, $context);
        }

        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null);
        $context = $this->initContext($resourceClass, $context);
        $iri = $this->iriConverter->getIriFromItem($object);
        $context['iri'] = $iri;
        $context['api_normalize'] = true;

        $data = parent::normalize($object, $format, $context);
        if (!\is_array($data)) {
            return $data;
        }

        $metadata = [
            '_links' => [
                'self' => [
                    'href' => $iri,
                ],
            ],
        ];
        $components = $this->getComponents($object, $format, $context);
        $metadata = $this->populateRelation($metadata, $object, $format, $context, $components, 'links');
        $metadata = $this->populateRelation($metadata, $object, $format, $context, $components, 'embedded');

        return $metadata + $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        // prevent the use of lower priority normalizers (e.g. serializer.normalizer.object) for this format
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        throw new LogicException(sprintf('%s is a read-only format.', self::FORMAT));
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes($object, $format = null, array $context = []): array
    {
        return $this->getComponents($object, $format, $context)['states'];
    }

    /**
     * Gets HAL components of the resource: states, links and embedded.
     *
     * @param object $object
     */
    private function getComponents($object, ?string $format, array $context): array
    {
        $cacheKey = $this->getObjectClass($object).'-'.$context['cache_key'];

        if (isset($this->componentsCache[$cacheKey])) {
            return $this->componentsCache[$cacheKey];
        }

        $attributes = parent::getAttributes($object, $format, $context);
        $options = $this->getFactoryOptions($context);

        $components = [
            'states' => [],
            'links' => [],
            'embedded' => [],
        ];

        foreach ($attributes as $attribute) {
            $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $options);

            $type = $propertyMetadata->getType();
            $isOne = $isMany = false;

            if (null !== $type) {
                if ($type->isCollection()) {
                    $valueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType();
                    $isMany = null !== $valueType && ($className = $valueType->getClassName()) && $this->resourceClassResolver->isResourceClass($className);
                } else {
                    $className = $type->getClassName();
                    $isOne = $className && $this->resourceClassResolver->isResourceClass($className);
                }
            }

            if (!$isOne && !$isMany) {
                $components['states'][] = $attribute;
                continue;
            }

            $relation = ['name' => $attribute, 'cardinality' => $isOne ? 'one' : 'many'];
            if ($propertyMetadata->isReadableLink()) {
                $components['embedded'][] = $relation;
            }

            $components['links'][] = $relation;
        }

        if (false !== $context['cache_key']) {
            $this->componentsCache[$cacheKey] = $components;
        }

        return $components;
    }

    /**
     * Populates _links and _embedded keys.
     *
     * @param object $object
     */
    private function populateRelation(array $data, $object, ?string $format, array $context, array $components, string $type): array
    {
        $class = $this->getObjectClass($object);

        $attributesMetadata = \array_key_exists($class, $this->attributesMetadataCache) ?
            $this->attributesMetadataCache[$class] :
            $this->attributesMetadataCache[$class] = $this->classMetadataFactory ? $this->classMetadataFactory->getMetadataFor($class)->getAttributesMetadata() : null;

        $key = '_'.$type;
        foreach ($components[$type] as $relation) {
            if (null !== $attributesMetadata && $this->isMaxDepthReached($attributesMetadata, $class, $relation['name'], $context)) {
                continue;
            }

            $attributeValue = $this->getAttributeValue($object, $relation['name'], $format, $context);
            if (empty($attributeValue)) {
                continue;
            }

            $relationName = $relation['name'];
            if ($this->nameConverter) {
                $relationName = $this->nameConverter->normalize($relationName, $class, $format, $context);
            }

            if ('one' === $relation['cardinality']) {
                if ('links' === $type) {
                    $data[$key][$relationName]['href'] = $this->getRelationIri($attributeValue);
                    continue;
                }

                $data[$key][$relationName] = $attributeValue;
                continue;
            }

            // many
            $data[$key][$relationName] = [];
            foreach ($attributeValue as $rel) {
                if ('links' === $type) {
                    $rel = ['href' => $this->getRelationIri($rel)];
                }

                $data[$key][$relationName][] = $rel;
            }
        }

        return $data;
    }

    /**
     * Gets the IRI of the given relation.
     *
     * @throws UnexpectedValueException
     */
    private function getRelationIri($rel): string
    {
        if (!(\is_array($rel) || \is_string($rel))) {
            throw new UnexpectedValueException('Expected relation to be an IRI or array');
        }

        return \is_string($rel) ? $rel : $rel['_links']['self']['href'];
    }

    /**
     * Is the max depth reached for the given attribute?
     *
     * @param AttributeMetadataInterface[] $attributesMetadata
     */
    private function isMaxDepthReached(array $attributesMetadata, string $class, string $attribute, array &$context): bool
    {
        if (
            !($context[self::ENABLE_MAX_DEPTH] ?? false) ||
            !isset($attributesMetadata[$attribute]) ||
            null === $maxDepth = $attributesMetadata[$attribute]->getMaxDepth()
        ) {
            return false;
        }

        $key = sprintf(self::DEPTH_KEY_PATTERN, $class, $attribute);
        if (!isset($context[$key])) {
            $context[$key] = 1;

            return false;
        }

        if ($context[$key] === $maxDepth) {
            return true;
        }

        ++$context[$key];

        return false;
    }
}
