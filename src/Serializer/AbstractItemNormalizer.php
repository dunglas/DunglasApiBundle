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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\ItemNormalizer as ElasticsearchItemNormalizer;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\InvalidValueException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer as GraphQlItemNormalizer;
use ApiPlatform\Core\Hal\Serializer\ItemNormalizer as HalItemNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer as JsonApiItemNormalizer;
use ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer as JsonLdItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Base item normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractItemNormalizer extends AbstractObjectNormalizer implements ContextAwareNormalizerInterface
{
    private const FORMATS = [GraphQlItemNormalizer::FORMAT, HalItemNormalizer::FORMAT, JsonApiItemNormalizer::FORMAT, JsonLdItemNormalizer::FORMAT];
    const USE_API_PLATFORM = 'use_api_platform';

    use ClassInfoTrait;
    use ContextTrait;
    use InputOutputMetadataTrait;

    protected $propertyNameCollectionFactory;
    protected $propertyMetadataFactory;
    protected $iriConverter;
    protected $resourceClassResolver;
    protected $propertyAccessor;
    protected $localCache = [];
    protected $itemDataProvider;
    protected $allowPlainIdentifiers;
    protected $allowUnmappedClass;
    protected $dataTransformers = [];

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, ItemDataProviderInterface $itemDataProvider = null, bool $allowPlainIdentifiers = false, array $defaultContext = [], iterable $dataTransformers = [], ResourceMetadataFactoryInterface $resourceMetadataFactory = null, bool $allowUnmappedClass = false)
    {
        if (!isset($defaultContext['circular_reference_handler'])) {
            $defaultContext['circular_reference_handler'] = function ($object) {
                return $this->iriConverter->getIriFromItem($object);
            };
        }
        if (!interface_exists(AdvancedNameConverterInterface::class)) {
            $this->setCircularReferenceHandler($defaultContext['circular_reference_handler']);
        }

        if (false === $allowUnmappedClass) {
            @trigger_error(sprintf('Passing a falsy $allowUnmappedClass flag in %s is deprecated since version 2.4 and will default to true in 3.0.', self::class), E_USER_DEPRECATED);
        }
        $defaultContext[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES] = false;

        parent::__construct($classMetadataFactory, $nameConverter, null, null, \Closure::fromCallable([$this, 'getObjectClass']), $defaultContext);

        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->itemDataProvider = $itemDataProvider;
        $this->allowPlainIdentifiers = $allowPlainIdentifiers;
        $this->dataTransformers = $dataTransformers;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->allowUnmappedClass = $allowUnmappedClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        if (!(($context[static::USE_API_PLATFORM] ?? false) || \in_array($format, self::FORMATS, true))) {
            return false;
        }

        if (!\is_object($data) || $data instanceof \Traversable) {
            return false;
        }

        if (false === $this->allowUnmappedClass) {
            return $this->resourceClassResolver->isResourceClass($this->getObjectClass($data));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        try {
            $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        } catch (InvalidArgumentException $e) {
            $context = $this->initContext(\get_class($object), $context);
            $context['api_normalize'] = true;

            return parent::normalize($object, $format, $context);
        }

        $context = $this->initContext($resourceClass, $context);
        $context['api_normalize'] = true;

        if (isset($context['resources'])) {
            $resource = $context['iri'] ?? $this->iriConverter->getIriFromItem($object);
            $context['resources'][$resource] = $resource;
        }

        return parent::normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if (ElasticsearchItemNormalizer::FORMAT === $format) {
            return false;
        }

        if (false === $this->allowUnmappedClass) {
            return $this->localCache[$type] ?? $this->localCache[$type] = $this->resourceClassResolver->isResourceClass($type);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $context['api_denormalize'] = true;
        $context['resource_class'] = $class;
        $inputClass = $this->getInputClass($class, $context);

        if (null !== $inputClass && null !== $dataTransformer = $this->getDataTransformer($data, $class, $context)) {
            $data = $dataTransformer->transform(
                parent::denormalize($data, $inputClass, $format, ['resource_class' => $inputClass] + $context),
                $class,
                $context
            );

            return $data;
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * Method copy-pasted from symfony/serializer.
     * Remove it after symfony/serializer version update @link https://github.com/symfony/symfony/pull/28263.
     *
     * {@inheritdoc}
     *
     * @internal
     */
    protected function instantiateObject(array &$data, $class, array &$context, \ReflectionClass $reflectionClass, $allowedAttributes, string $format = null)
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, static::OBJECT_TO_POPULATE)) {
            unset($context[static::OBJECT_TO_POPULATE]);

            return $object;
        }

        if ($this->classDiscriminatorResolver && $mapping = $this->classDiscriminatorResolver->getMappingForClass($class)) {
            if (!isset($data[$mapping->getTypeProperty()])) {
                throw new RuntimeException(sprintf('Type property "%s" not found for the abstract object "%s"', $mapping->getTypeProperty(), $class));
            }

            $type = $data[$mapping->getTypeProperty()];
            if (null === ($mappedClass = $mapping->getClassForType($type))) {
                throw new RuntimeException(sprintf('The type "%s" has no mapped class for the abstract object "%s"', $type, $class));
            }

            $class = $mappedClass;
            $reflectionClass = new \ReflectionClass($class);
        }

        $constructor = $this->getConstructor($data, $class, $context, $reflectionClass, $allowedAttributes);
        if ($constructor) {
            $constructorParameters = $constructor->getParameters();

            $params = [];
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = $constructorParameter->name;
                $key = $this->nameConverter ? $this->nameConverter->normalize($paramName, $class, $format, $context) : $paramName;

                $allowed = false === $allowedAttributes || (\is_array($allowedAttributes) && \in_array($paramName, $allowedAttributes, true));
                $ignored = !$this->isAllowedAttribute($class, $paramName, $format, $context);
                if ($constructorParameter->isVariadic()) {
                    if ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                        if (!\is_array($data[$paramName])) {
                            throw new RuntimeException(sprintf('Cannot create an instance of %s from serialized data because the variadic parameter %s can only accept an array.', $class, $constructorParameter->name));
                        }

                        $params = array_merge($params, $data[$paramName]);
                    }
                } elseif ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                    $params[] = $this->createConstructorArgument($data[$key], $key, $constructorParameter, $context, $format);

                    // Don't run set for a parameter passed to the constructor
                    unset($data[$key]);
                } elseif (isset($context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key])) {
                    $params[] = $context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key];
                } elseif ($constructorParameter->isDefaultValueAvailable()) {
                    $params[] = $constructorParameter->getDefaultValue();
                } else {
                    throw new MissingConstructorArgumentsException(
                        sprintf(
                            'Cannot create an instance of %s from serialized data because its constructor requires parameter "%s" to be present.',
                            $class,
                            $constructorParameter->name
                        )
                    );
                }
            }

            if ($constructor->isConstructor()) {
                return $reflectionClass->newInstanceArgs($params);
            }

            return $constructor->invokeArgs(null, $params);
        }

        return new $class();
    }

    /**
     * {@inheritdoc}
     */
    protected function createConstructorArgument($parameterData, string $key, \ReflectionParameter $constructorParameter, array &$context, string $format = null)
    {
        return $this->createAttributeValue($constructorParameter->name, $parameterData, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * Unused in this context.
     */
    protected function extractAttributes($object, $format = null, array $context = [])
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
    {
        $options = $this->getFactoryOptions($context);
        $propertyNames = $this->propertyNameCollectionFactory->create($context['resource_class'], $options);

        $allowedAttributes = [];
        foreach ($propertyNames as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $propertyName, $options);

            if (
                $this->isAllowedAttribute($classOrObject, $propertyName, null, $context) &&
                (
                    isset($context['api_normalize']) && $propertyMetadata->isReadable() ||
                    isset($context['api_denormalize']) && ($propertyMetadata->isWritable() || !\is_object($classOrObject) && $propertyMetadata->isInitializable())
                )
            ) {
                $allowedAttributes[] = $propertyName;
            }
        }

        return $allowedAttributes;
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $this->setValue($object, $attribute, $this->createAttributeValue($attribute, $value, $format, $context));
    }

    private function createAttributeValue($attribute, $value, $format = null, array $context = [])
    {
        if (!\is_string($attribute)) {
            throw new InvalidValueException('Invalid value provided (invalid IRI?).');
        }

        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));
        $type = $propertyMetadata->getType();

        if (null === $type) {
            // No type provided, blindly return the value
            return $value;
        }

        if (null === $value && $type->isNullable()) {
            return $value;
        }

        if (
            $type->isCollection() &&
            null !== ($collectionValueType = $type->getCollectionValueType()) &&
            null !== $className = $collectionValueType->getClassName()
        ) {
            return $this->denormalizeCollection($attribute, $propertyMetadata, $type, $className, $value, $format, $context);
        }

        if (null !== $className = $type->getClassName()) {
            return $this->denormalizeRelation($attribute, $propertyMetadata, $className, $value, $format, $this->createChildContext($context, $attribute));
        }

        if ($context[static::DISABLE_TYPE_ENFORCEMENT] ?? false) {
            return $value;
        }

        $this->validateType($attribute, $type, $value, $format);

        return $value;
    }

    /**
     * Validates the type of the value. Allows using integers as floats for JSON formats.
     *
     * @throws InvalidArgumentException
     */
    protected function validateType(string $attribute, Type $type, $value, string $format = null)
    {
        $builtinType = $type->getBuiltinType();
        if (Type::BUILTIN_TYPE_FLOAT === $builtinType && null !== $format && false !== strpos($format, 'json')) {
            $isValid = \is_float($value) || \is_int($value);
        } else {
            $isValid = \call_user_func('is_'.$builtinType, $value);
        }

        if (!$isValid) {
            throw new InvalidArgumentException(sprintf(
                'The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $builtinType, \gettype($value)
            ));
        }
    }

    /**
     * Denormalizes a collection of objects.
     *
     * @throws InvalidArgumentException
     */
    protected function denormalizeCollection(string $attribute, PropertyMetadata $propertyMetadata, Type $type, string $className, $value, string $format = null, array $context): array
    {
        if (!\is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'The type of the "%s" attribute must be "array", "%s" given.', $attribute, \gettype($value)
            ));
        }

        $collectionKeyType = $type->getCollectionKeyType();
        $collectionKeyBuiltinType = null === $collectionKeyType ? null : $collectionKeyType->getBuiltinType();

        $values = [];
        foreach ($value as $index => $obj) {
            if (null !== $collectionKeyBuiltinType && !\call_user_func('is_'.$collectionKeyBuiltinType, $index)) {
                throw new InvalidArgumentException(sprintf(
                        'The type of the key "%s" must be "%s", "%s" given.',
                        $index, $collectionKeyBuiltinType, \gettype($index))
                );
            }

            $values[$index] = $this->denormalizeRelation($attribute, $propertyMetadata, $className, $obj, $format, $this->createChildContext($context, $attribute));
        }

        return $values;
    }

    /**
     * Denormalizes a relation.
     *
     * @throws InvalidArgumentException
     *
     * @return object|null
     */
    protected function denormalizeRelation(string $attributeName, PropertyMetadata $propertyMetadata, string $className, $value, string $format = null, array $context)
    {
        if (\is_string($value)) {
            try {
                return $this->iriConverter->getItemFromIri($value, $context + ['fetch_data' => true]);
            } catch (ItemNotFoundException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (InvalidArgumentException $e) {
                // Give a chance to other normalizers (e.g.: DateTimeNormalizer)
            }
        }

        if (
            !$this->resourceClassResolver->isResourceClass($className) ||
            $propertyMetadata->isWritableLink()
        ) {
            $context['resource_class'] = $className;
            $context['api_allow_update'] = true;

            try {
                if ($this->serializer instanceof DenormalizerInterface) {
                    return $this->serializer->denormalize($value, $className, $format, $context);
                }
                throw new InvalidArgumentException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
            } catch (InvalidValueException $e) {
                if (!$this->allowPlainIdentifiers || null === $this->itemDataProvider) {
                    throw $e;
                }
            }
        }

        if (!\is_array($value)) {
            // repeat the code so that IRIs keep working with the json format
            if (true === $this->allowPlainIdentifiers && $this->itemDataProvider) {
                $item = $this->itemDataProvider->getItem($className, $value, null, $context + ['fetch_data' => true]);
                if (null === $item) {
                    throw new ItemNotFoundException(sprintf('Item not found for "%s".', $value));
                }

                return $item;
            }

            throw new InvalidArgumentException(sprintf(
                'Expected IRI or nested document for attribute "%s", "%s" given.', $attributeName, \gettype($value)
            ));
        }

        throw new InvalidArgumentException(sprintf('Nested documents for attribute "%s" are not allowed. Use IRIs instead.', $attributeName));
    }

    /**
     * Sets a value of the object using the PropertyAccess component.
     *
     * @param object $object
     */
    private function setValue($object, string $attributeName, $value)
    {
        try {
            $this->propertyAccessor->setValue($object, $attributeName, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }

    /**
     * Gets a valid context for property metadata factories.
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php
     */
    protected function getFactoryOptions(array $context): array
    {
        $options = [];

        if (isset($context[self::GROUPS])) {
            $options['serializer_groups'] = $context[self::GROUPS];
        }

        if (isset($context['collection_operation_name'])) {
            $options['collection_operation_name'] = $context['collection_operation_name'];
        }

        if (isset($context['item_operation_name'])) {
            $options['item_operation_name'] = $context['item_operation_name'];
        }

        return $options;
    }

    /**
     * Creates the context to use when serializing a relation.
     *
     * @deprecated since version 2.1, to be removed in 3.0.
     */
    protected function createRelationSerializationContext(string $resourceClass, array $context): array
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.1 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        return $context;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NoSuchPropertyException
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        try {
            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
        } catch (NoSuchPropertyException $e) {
            if (null === $propertyMetadata->isChildInherited()) {
                throw $e;
            }

            $attributeValue = null;
        }

        $type = $propertyMetadata->getType();

        if (
            is_iterable($attributeValue) &&
            $type &&
            $type->isCollection() &&
            ($collectionValueType = $type->getCollectionValueType()) &&
            ($className = $collectionValueType->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            return $this->normalizeCollectionOfRelations($propertyMetadata, $attributeValue, $className, $format, $this->createChildContext($context, $attribute));
        }

        if (
            $type &&
            ($className = $type->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            return $this->normalizeRelation($propertyMetadata, $attributeValue, $className, $format, $this->createChildContext($context, $attribute));
        }

        unset($context['resource_class']);

        if ($this->serializer instanceof NormalizerInterface) {
            return $this->serializer->normalize($attributeValue, $format, $context);
        }
        throw new InvalidArgumentException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
    }

    /**
     * Normalizes a collection of relations (to-many).
     *
     * @param iterable $attributeValue
     */
    protected function normalizeCollectionOfRelations(PropertyMetadata $propertyMetadata, $attributeValue, string $resourceClass, string $format = null, array $context): array
    {
        $value = [];
        foreach ($attributeValue as $index => $obj) {
            $value[$index] = $this->normalizeRelation($propertyMetadata, $obj, $resourceClass, $format, $context);
        }

        return $value;
    }

    /**
     * Normalizes a relation as an object if is a Link or as an URI.
     *
     * @return string|array
     */
    protected function normalizeRelation(PropertyMetadata $propertyMetadata, $relatedObject, string $resourceClass, string $format = null, array $context)
    {
        if (null === $relatedObject || !empty($context['attributes']) || $propertyMetadata->isReadableLink()) {
            if (null === $relatedObject) {
                unset($context['resource_class']);
            } else {
                $context['resource_class'] = $resourceClass;
            }

            if ($this->serializer instanceof NormalizerInterface) {
                return $this->serializer->normalize($relatedObject, $format, $context);
            }
            throw new InvalidArgumentException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
        }

        $iri = $this->iriConverter->getIriFromItem($relatedObject);
        if (isset($context['resources'])) {
            $context['resources'][$iri] = $iri;
        }
        if (isset($context['resources_to_push']) && $propertyMetadata->getAttribute('push', false)) {
            $context['resources_to_push'][$iri] = $iri;
        }

        return $iri;
    }

    /**
     * Finds the first supported data transformer if any.
     */
    protected function getDataTransformer($object, string $to, array $context = []): ?DataTransformerInterface
    {
        foreach ($this->dataTransformers as $dataTransformer) {
            if ($dataTransformer->supportsTransformation($object, $to, $context)) {
                return $dataTransformer;
            }
        }

        return null;
    }

    /**
     * For a given resource, it returns an output representation if any
     * If not, the resource is returned.
     */
    protected function transformOutput($object, array $context = [])
    {
        $outputClass = $this->getOutputClass($this->getObjectClass($object), $context);
        if (null !== $outputClass && null !== $dataTransformer = $this->getDataTransformer($object, $outputClass, $context)) {
            return $dataTransformer->transform($object, $outputClass, $context);
        }

        return $object;
    }
}
