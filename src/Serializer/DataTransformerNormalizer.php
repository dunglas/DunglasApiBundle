<?php

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Transform data before denormalization
 */
class DataTransformerNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    use InputOutputMetadataTrait;

    /** @var DenormalizerInterface|NormalizerInterface */
    private $normalizer;

    /**
     * @var iterable|DataTransformerInterface[]
     */
    private $dataTransformers;

    public function __construct(NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, iterable $dataTransformers = [])
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \TypeError('Normalizer should be an instance of '.DenormalizerInterface::class);
        }

        $this->normalizer = $normalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->dataTransformers = $dataTransformers;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $inputClass = $this->getInputClass($class, $context);

        if (null !== $inputClass && null !== $dataTransformer = $this->getDataTransformer($data, $class, $context)) {
            $data = $dataTransformer->transform(
                $this->normalizer->denormalize($data, $inputClass, $format, ['resource_class' => $inputClass] + $context),
                $class,
                $context
            );
        }

        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->normalizer->supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->normalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        if ($this->normalizer instanceof CacheableSupportsMethodInterface) {
            return $this->normalizer->hasCacheableSupportsMethod();
        }

        return false;
    }

    /**
     * Finds the first supported data transformer if any.
     */
    private function getDataTransformer($object, string $to, array $context = []): ?DataTransformerInterface
    {
        foreach ($this->dataTransformers as $dataTransformer) {
            if ($dataTransformer->supportsTransformation($object, $to, $context)) {
                return $dataTransformer;
            }
        }

        return null;
    }
}
