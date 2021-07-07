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

namespace ApiPlatform\Core\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Common features regarding Constraint Violation normalization.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
abstract class AbstractConstraintViolationListNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = null; // Must be overrode

    private $serializePayloadFields;
    private $nameConverter;

    public function __construct(array $serializePayloadFields = null, NameConverterInterface $nameConverter = null)
    {
        $this->nameConverter = $nameConverter;
        $this->serializePayloadFields = null === $serializePayloadFields ? null : array_flip($serializePayloadFields);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return static::FORMAT === $format && $data instanceof ConstraintViolationListInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    protected function getMessagesAndViolations(ConstraintViolationListInterface $constraintViolationList): array
    {
        $violations = $messages = [];

        foreach ($constraintViolationList as $violation) {
            $class = \is_object($root = $violation->getRoot()) ? \get_class($root) : null;
            $violationData = [
                'propertyPath' => $this->nameConverter ? $this->nameConverter->normalize($violation->getPropertyPath(), $class, static::FORMAT) : $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'code' => $violation->getCode(),
            ];

            $constraint = $violation instanceof ConstraintViolation ? $violation->getConstraint() : null;
            if (
                [] !== $this->serializePayloadFields &&
                $constraint &&
                $constraint->payload &&
                // If some fields are whitelisted, only them are added
                $payloadFields = null === $this->serializePayloadFields ? $constraint->payload : array_intersect_key($constraint->payload, $this->serializePayloadFields)
            ) {
                $violationData['payload'] = $payloadFields;
            }

            $violations[] = $violationData;
            $messages[] = ($violationData['propertyPath'] ? "{$violationData['propertyPath']}: " : '').$violationData['message'];
        }

        return [$messages, $violations];
    }
}
