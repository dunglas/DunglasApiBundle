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

namespace ApiPlatform\Core\OpenApi\Serializer;

use ApiPlatform\Core\OpenApi\OpenApi;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Generates an OpenAPI v3 specification.
 */
final class OpenApiNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'json';
    private const EXTENSION_PROPERTIES_KEY = 'extensionProperties';

    private $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $context[AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS] = true;
        $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] = true;

        return $this->recursiveClean($this->decorated->normalize($object, $format, $context));
    }

    private function recursiveClean($data): array
    {
        foreach ($data as $key => $value) {
            if (self::EXTENSION_PROPERTIES_KEY === $key) {
                foreach ($data[self::EXTENSION_PROPERTIES_KEY] as $extensionPropertyKey => $extensionPropertyValue) {
                    $data[$extensionPropertyKey] = $extensionPropertyValue;
                }
                continue;
            }

            if ('schemas' === $key && \is_array($value)) {
                ksort($value);
            }

            // Side effect of using getPaths(): Paths which itself contains the array
            if ('paths' === $key) {
                $value = $data['paths'] = $data['paths']['paths'];
                if ($value) {
                    ksort($value);
                }
                unset($data['paths']['paths']);
            }

            if (\is_array($value)) {
                $data[$key] = $this->recursiveClean($value);
                // arrays must stay even if empty
                continue;
            }
        }

        unset($data[self::EXTENSION_PROPERTIES_KEY]);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return self::FORMAT === $format && $data instanceof OpenApi;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
