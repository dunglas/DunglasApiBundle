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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Serializer\AbstractConstraintViolationListNormalizer;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} to a Hydra error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer extends AbstractConstraintViolationListNormalizer
{
    public const FORMAT = 'jsonld';

    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator, array $serializePayloadFields = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($serializePayloadFields, $nameConverter);

        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        [$messages, $violations] = $this->getMessagesAndViolations($object);

        return [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList']),
            '@type' => 'ConstraintViolationList',
            'hydra:title' => $context['title'] ?? 'An error occurred',
            'hydra:description' => $messages ? implode("\n", $messages) : (string) $object,
            'violations' => $violations,
        ];
    }
}
