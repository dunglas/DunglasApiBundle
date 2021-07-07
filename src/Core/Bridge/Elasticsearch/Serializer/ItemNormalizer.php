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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Serializer;

use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Item normalizer decorator that prevents {@see \ApiPlatform\Core\Serializer\ItemNormalizer}
 * from taking over for the {@see DocumentNormalizer::FORMAT} format because of priorities.
 *
 * @experimental
 */
final class ItemNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface, CacheableSupportsMethodInterface
{
    private $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function hasCacheableSupportsMethod(): bool
    {
        if (!$this->decorated instanceof CacheableSupportsMethodInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', CacheableSupportsMethodInterface::class));
        }

        return $this->decorated->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (!$this->decorated instanceof DenormalizerInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', DenormalizerInterface::class));
        }

        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if (!$this->decorated instanceof DenormalizerInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', DenormalizerInterface::class));
        }

        return DocumentNormalizer::FORMAT !== $format && $this->decorated->supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->decorated->normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return DocumentNormalizer::FORMAT !== $format && $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$this->decorated instanceof SerializerAwareInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', SerializerAwareInterface::class));
        }

        $this->decorated->setSerializer($serializer);
    }
}
