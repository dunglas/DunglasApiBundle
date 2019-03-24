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
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class JsonLdItemNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    use JsonLdContextTrait;

    const FORMAT = 'jsonld';

    private $normalizer;

    private $iriConverter;

    private $resourceMetadataFactory;

    private $contextBuilder;

    public function __construct(NormalizerInterface $normalizer, IriConverterInterface $iriConverter, ResourceMetadataFactoryInterface $resourceMetadataFactory, ContextBuilderInterface $contextBuilder)
    {
        $this->normalizer = $normalizer;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return self::FORMAT === $format && $this->normalizer->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $rawData = $this->normalizer->normalize($object, $format, $context);

        if (!\is_array($rawData)) {
            return $rawData;
        }

        $resourceClass = $context['resource_class'] ?? null;

        if (!\is_string($resourceClass)) {
            return $this->normalizer->normalize($object, $format, $context);
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);

        if (!isset($context['iri'])) {
            $context['iri'] = $this->iriConverter->getIriFromItem($object);
        }

        $data['@id'] = $context['iri'];
        $data['@type'] = $resourceMetadata->getIri() ?: $resourceMetadata->getShortName();

        return array_merge($data, $rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return self::FORMAT === $format && $this->normalizer->supportsNormalization($data, $format, $context);
    }
}
