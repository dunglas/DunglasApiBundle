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

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Normalize validation exceptions.
 *
 * @experimental
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ValidationExceptionNormalizer implements NormalizerInterface
{
    private $exceptionToStatus;

    public function __construct(array $exceptionToStatus = [])
    {
        $this->exceptionToStatus = $exceptionToStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        /** @var ValidationException */
        $validationException = $object->getPrevious();
        $error = FormattedError::createFromException($object);
        $error['message'] = $validationException->getMessage();

        $exceptionClass = \get_class($validationException);
        $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

        foreach ($this->exceptionToStatus as $class => $status) {
            if (is_a($exceptionClass, $class, true)) {
                $statusCode = $status;

                break;
            }
        }
        $error['extensions']['status'] = $statusCode;
        $error['extensions']['category'] = 'user';
        $error['extensions']['violations'] = [];

        /** @var ConstraintViolation $violation */
        foreach ($validationException->getConstraintViolationList() as $violation) {
            $error['extensions']['violations'][] = [
                'path' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Error && $data->getPrevious() instanceof ValidationException;
    }
}
