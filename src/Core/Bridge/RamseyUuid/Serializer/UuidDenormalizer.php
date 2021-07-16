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

namespace ApiPlatform\Core\Bridge\RamseyUuid\Serializer;

use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UuidDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        try {
            return Uuid::fromString($data);
        } catch (InvalidUuidStringException $e) {
            throw new NotNormalizableValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return \is_string($data) && is_a($type, UuidInterface::class, true);
    }
}
