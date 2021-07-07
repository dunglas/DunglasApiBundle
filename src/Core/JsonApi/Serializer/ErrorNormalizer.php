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

use ApiPlatform\Core\Problem\Serializer\ErrorNormalizerTrait;
use Symfony\Component\Debug\Exception\FlattenException as LegacyFlattenException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts {@see \Exception} or {@see FlattenException} or {@see LegacyFlattenException}  to a JSON API error representation.
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class ErrorNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use ErrorNormalizerTrait;

    public const FORMAT = 'jsonapi';
    public const TITLE = 'title';

    private $debug;
    private $defaultContext = [
        self::TITLE => 'An error occurred',
    ];

    public function __construct(bool $debug = false, array $defaultContext = [])
    {
        $this->debug = $debug;
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $data = [
            'title' => $context[self::TITLE] ?? $this->defaultContext[self::TITLE],
            'description' => $this->getErrorMessage($object, $context, $this->debug),
        ];

        if (null !== $errorCode = $this->getErrorCode($object)) {
            $data['code'] = $errorCode;
        }

        if ($this->debug && null !== $trace = $object->getTrace()) {
            $data['trace'] = $trace;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException || $data instanceof LegacyFlattenException);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
