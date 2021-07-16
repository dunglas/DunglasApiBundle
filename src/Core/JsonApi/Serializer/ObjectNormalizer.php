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

namespace ApiPlatform\Core\JsonApi\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Decorates the output with JSON API metadata when appropriate, but otherwise
 * just passes through to the decorated normalizer.
 */
final class ObjectNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use ClassInfoTrait;

    public const FORMAT = 'jsonapi';

    private $decorated;
    private $iriConverter;
    private $resourceClassResolver;
    private $resourceMetadataFactory;

    public function __construct(NormalizerInterface $decorated, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $this->decorated->supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return $this->decorated instanceof CacheableSupportsMethodInterface && $this->decorated->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (isset($context['api_resource'])) {
            $originalResource = $context['api_resource'];
            unset($context['api_resource']);
        }

        $data = $this->decorated->normalize($object, $format, $context);
        if (!\is_array($data) || isset($context['api_attribute'])) {
            return $data;
        }

        if (isset($originalResource)) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($originalResource);
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $resourceData = [
                'id' => $this->iriConverter->getIriFromItem($originalResource),
                'type' => $resourceMetadata->getShortName(),
            ];
        } else {
            $resourceData = [
                'id' => \function_exists('spl_object_id') ? spl_object_id($object) : spl_object_hash($object),
                'type' => (new \ReflectionClass($this->getObjectClass($object)))->getShortName(),
            ];
        }

        if ($data) {
            $resourceData['attributes'] = $data;
        }

        return ['data' => $resourceData];
    }
}
