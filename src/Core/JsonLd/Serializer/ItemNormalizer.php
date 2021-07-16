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

namespace ApiPlatform\Core\JsonLd\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ClassInfoTrait;
    use ContextTrait;
    use JsonLdContextTrait;

    public const FORMAT = 'jsonld';

    private $contextBuilder;

    public function __construct($resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], iterable $dataTransformers = [], ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, null, false, $defaultContext, $dataTransformers, $resourceMetadataFactory, $resourceAccessChecker);

        if ($iriConverter instanceof LegacyIriConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', IriConverterInterface::class, LegacyIriConverterInterface::class));
        }

        $this->contextBuilder = $contextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $objectClass = $this->getObjectClass($object);
        $outputClass = $this->getOutputClass($objectClass, $context);
        if (null !== $outputClass && !isset($context[self::IS_TRANSFORMED_TO_SAME_CLASS])) {
            return parent::normalize($object, $format, $context);
        }

        // TODO: we should not remove the resource_class in the normalizeRawCollection as we would find out it's not the same anyway as the requested one
        $previousResourceClass = $context['resource_class'] ?? null;
        $context = $this->initContext($context['resource_class'] ?? $outputClass ?? $objectClass, $context);
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $metadata = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);

        if (isset($context['operation'])) {
            if ($previousResourceClass !== $resourceClass) {
                unset($context['operation'], $context['operation_name']);
            }
        }

        if ($this->iriConverter instanceof IriConverterInterface) {
            $iri = $this->iriConverter->getIriFromItem($object, $context['operation_name'] ?? null, UrlGeneratorInterface::ABS_PATH, $context);
        } else {
            $iri = $this->iriConverter->getIriFromItem($object);
        }

        $context['iri'] = $iri;
        $metadata['@id'] = $iri;
        $context['api_normalize'] = true;

        $data = parent::normalize($object, $format, $context);
        if (!\is_array($data)) {
            return $data;
        }

        // TODO: remove in 3.0
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $metadata['@type'] = $resourceMetadata->getIri() ?: $resourceMetadata->getShortName();
        } elseif ($this->resourceMetadataFactory) {
            $operation = $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation();
            $types = $operation->getTypes() ?? [$operation->getShortName()];
            $metadata['@type'] = 1 === \count($types) ? $types[0] : $types;
        }

        return $metadata + $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['@id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri($data['@id'], $context + ['fetch_data' => true]);
        }

        return parent::denormalize($data, $class, $format, $context);
    }
}
