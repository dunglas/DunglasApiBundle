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

namespace ApiPlatform\Core\GraphQl\Serializer\Exception;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize runtime exceptions to have the right message in production mode.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class RuntimeExceptionNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        /** @var \RuntimeException */
        $runtimeException = $object->getPrevious();
        $error = FormattedError::createFromException($object);
        $error['message'] = $runtimeException->getMessage();

        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Error && $data->getPrevious() instanceof \RuntimeException;
    }
}
