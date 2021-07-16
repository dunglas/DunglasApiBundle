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
use ApiPlatform\Core\Api\Entrypoint;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'jsonld';

    private $resourceMetadataFactory;
    private $iriConverter;
    private $urlGenerator;

    public function __construct($resourceMetadataFactory, $iriConverter, UrlGeneratorInterface $urlGenerator)
    {
        if ($iriConverter instanceof LegacyIriConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', IriConverterInterface::class, LegacyIriConverterInterface::class));
        }

        $this->iriConverter = $iriConverter;
        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceCollectionMetadataFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entrypoint = [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'Entrypoint']),
            '@id' => $this->urlGenerator->generate('api_entrypoint'),
            '@type' => 'Entrypoint',
        ];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($resourceMetadata instanceof ResourceMetadata) {
                if (empty($resourceMetadata->getCollectionOperations())) {
                    continue;
                }
                try {
                    $entrypoint[lcfirst($resourceMetadata->getShortName())] = $this->iriConverter->getIriFromResourceClass($resourceClass);
                } catch (InvalidArgumentException $ex) {
                    // Ignore resources without GET operations
                }
                continue;
            }

            foreach ($resourceMetadata as $resource) {
                foreach ($resource->operations as $operationName => $operation) {
                    if (!$operation->collection) {
                        continue;
                    }
                    try {
                        $entrypoint[lcfirst($resource->shortName)] = $this->iriConverter instanceof IriConverterInterface ? $this->iriConverter->getIriFromResourceClass($resourceClass, $operationName) : $this->iriConverter->getIriFromResourceClass($resourceClass);
                    } catch (InvalidArgumentException $ex) {
                        // Ignore resources without GET operations
                    }
                }
            }
        }

        ksort($entrypoint);

        return $entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return self::FORMAT === $format && $data instanceof Entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
