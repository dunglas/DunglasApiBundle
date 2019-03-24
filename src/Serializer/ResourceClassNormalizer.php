<?php

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\ItemNormalizer as ElasticsearchItemNormalizer;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Add resource class to the context
 */
final class ResourceClassNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    use ContextTrait;
    use ClassInfoTrait;

    /** @var DenormalizerInterface|NormalizerInterface */
    private $normalizer;

    private $resourceClassResolver;

    private $iriConverter;

    private $localCache = [];

    public function __construct(NormalizerInterface $normalizer, ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('Normalizer should be an instance of '.DenormalizerInterface::class);
        }

        $this->normalizer = $normalizer;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $context['api_denormalize'] = true;
        $context['resource_class'] = $class;

        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->localCache[$type] ?? $this->localCache[$type] = $this->resourceClassResolver->isResourceClass($type);
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

            return $this->normalizer->normalize($object, $format, $context);
        }

        $context = $this->initContext($resourceClass, $context);
        $context['api_normalize'] = true;

        if (isset($context['resources'])) {
            $resource = $context['iri'] ?? $this->iriConverter->getIriFromItem($object);
            $context['resources'][$resource] = $resource;
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (!\is_object($data) || $data instanceof \Traversable) {
            return false;
        }

        return
            $this->resourceClassResolver->isResourceClass($this->getObjectClass($data))
            &&
            $this->normalizer->supportsNormalization($data, $format)
        ;
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
}
