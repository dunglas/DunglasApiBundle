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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\ObjectClassResolver;
use ApiPlatform\Core\Serializer\ResourceClassNormalizer;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface, SerializerAwareInterface
{
//    use ClassInfoTrait;
//    use ContextTrait;
//    use JsonLdContextTrait;

    const FORMAT = 'jsonld';

//    private $contextBuilder;

    private $objectNormalizer;

    private $normalizer;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], iterable $dataTransformers = [], bool $handleNonResource = false)
    {
//        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, null, false, $defaultContext, $dataTransformers, $resourceMetadataFactory, $handleNonResource);

//        $this->contextBuilder = $contextBuilder;
        $this->objectNormalizer = new ObjectNormalizer(
            $classMetadataFactory,
            $nameConverter,
            $propertyAccessor,
            null,
            null,
            new ObjectClassResolver(),
            $defaultContext
        );

        $jsonLdItemNormalizer = new JsonLdItemNormalizer(
            $this->objectNormalizer,
            $iriConverter,
            $resourceMetadataFactory,
            $contextBuilder
        );

        $resourceClassNormalizer = new ResourceClassNormalizer(
            $jsonLdItemNormalizer,
            $resourceClassResolver,
            $iriConverter
        );

        $this->normalizer = $resourceClassNormalizer;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->objectNormalizer->setSerializer($serializer);
    }


    public function hasCacheableSupportsMethod(): bool
    {
        return $this->normalizer->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $this->normalizer->supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->normalizer->normalize($object, $format, $context);
//        if ($this->handleNonResource && ($context['api_normalize'] ?? false) || null !== $outputClass = $this->getOutputClass($this->getObjectClass($object), $context)) {
//            if (isset($outputClass)) {
//                $object = $this->transformOutput($object, $context);
//            }
//
//            $data = $this->createJsonLdContext($this->contextBuilder, $object, $context);
//            $rawData = parent::normalize($object, $format, $context);
//            if (!\is_array($rawData)) {
//                return $rawData;
//            }
//
//            return $data + $rawData;
//        }
//
//        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
//        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
//        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
//
//        // Use resolved resource class instead of given resource class to support multiple inheritance child types
//        $context['resource_class'] = $resourceClass;
//        $context['iri'] = $this->iriConverter->getIriFromItem($object);
//
//        $rawData = parent::normalize($object, $format, $context);
//        if (!\is_array($rawData)) {
//            return $rawData;
//        }
//
//        $data['@id'] = $context['iri'];
//        $data['@type'] = $resourceMetadata->getIri() ?: $resourceMetadata->getShortName();
//
//        return $data + $rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return $this->normalizer->supportsDenormalization($data, $type, $format, $context);
//        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->normalizer->denormalize($data, $class, $format, $context);
//        // Avoid issues with proxies if we populated the object
//        if (isset($data['@id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
//            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
//                throw new InvalidArgumentException('Update is not allowed for this operation.');
//            }
//
//            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri($data['@id'], $context + ['fetch_data' => true]);
//        }
//
//        return parent::denormalize($data, $class, $format, $context);
    }
}
