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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} to a JSON API error representation.
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class ConstraintViolationListNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'jsonapi';

    private $nameConverter;
    private $propertyMetadataFactory;

    public function __construct(PropertyMetadataFactoryInterface $propertyMetadataFactory, NameConverterInterface $nameConverter = null)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->nameConverter = $nameConverter;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $violations = [];
        foreach ($object as $violation) {
            $violations[] = [
                'detail' => $violation->getMessage(),
                'source' => [
                    'pointer' => $this->getSourcePointerFromViolation($violation),
                ],
            ];
        }

        return ['errors' => $violations];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof ConstraintViolationListInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    private function getSourcePointerFromViolation(ConstraintViolationInterface $violation)
    {
        $fieldName = $violation->getPropertyPath();

        if (!$fieldName) {
            return 'data';
        }

        $class = \get_class($violation->getRoot());
        $propertyMetadata = $this->propertyMetadataFactory
            ->create(
                // Im quite sure this requires some thought in case of validations over relationships
                $class,
                $fieldName
            );

        if (null !== $this->nameConverter) {
            $fieldName = $this->nameConverter->normalize($fieldName, $class, self::FORMAT);
        }

        if (($type = $propertyMetadata->getType()) && null !== $type->getClassName()) {
            return "data/relationships/$fieldName";
        }

        return "data/attributes/$fieldName";
    }
}
