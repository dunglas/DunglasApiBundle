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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\JsonLd\Serializer\JsonLdContextTrait;
use ApiPlatform\Core\Serializer\ContextTrait;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * This normalizer handles collections.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    use ContextTrait;
    use JsonLdContextTrait;
    use NormalizerAwareTrait;

    public const FORMAT = 'jsonld';
    public const IRI_ONLY = 'iri_only';

    private $contextBuilder;
    private $resourceClassResolver;
    private $iriConverter;
    private $defaultContext = [
        self::IRI_ONLY => false,
    ];

    public function __construct(ContextBuilderInterface $contextBuilder, ResourceClassResolverInterface $resourceClassResolver, $iriConverter, array $defaultContext = [])
    {
        $this->contextBuilder = $contextBuilder;
        $this->resourceClassResolver = $resourceClassResolver;

        if ($iriConverter instanceof LegacyIriConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', IriConverterInterface::class, LegacyIriConverterInterface::class));
        }
        $this->iriConverter = $iriConverter;
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && is_iterable($data);
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!isset($context['resource_class']) || isset($context['api_sub_level'])) {
            return $this->normalizeRawCollection($object, $format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $context = $this->initContext($resourceClass, $context);
        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);

        if ($this->iriConverter instanceof IriConverterInterface) {
            $data['@id'] = $this->iriConverter->getIriFromResourceClass($resourceClass, $context['operation_name'] ?? null, UrlGeneratorInterface::ABS_PATH, $context);
        } else {
            //TODO: remove in 3.0
            $data['@id'] = isset($context['operation_type']) && OperationType::SUBRESOURCE === $context['operation_type'] ? $this->iriConverter->getSubresourceIriFromResourceClass($resourceClass, $context) : $this->iriConverter->getIriFromResourceClass($resourceClass);
        }

        $data['@type'] = 'hydra:Collection';
        $data['hydra:member'] = [];
        $iriOnly = $context[self::IRI_ONLY] ?? $this->defaultContext[self::IRI_ONLY];
        unset($context['operation'], $context['operation_name']);

        foreach ($object as $obj) {
            if ($iriOnly) {
                $data['hydra:member'][] = $this->iriConverter->getIriFromItem($obj);
            } else {
                $data['hydra:member'][] = $this->normalizer->normalize($obj, $format, $context);
            }
        }

        if ($object instanceof PaginatorInterface) {
            $data['hydra:totalItems'] = $object->getTotalItems();
        }
        if (\is_array($object) || ($object instanceof \Countable && !$object instanceof PartialPaginatorInterface)) {
            $data['hydra:totalItems'] = \count($object);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * Normalizes a raw collection (not API resources).
     */
    private function normalizeRawCollection(iterable $object, ?string $format, array $context): array
    {
        $data = [];
        foreach ($object as $index => $obj) {
            $data[$index] = $this->normalizer->normalize($obj, $format, $context);
        }

        return $data;
    }
}
