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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Serializer\NameConverter;

use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts inner fields with a decorated name converter.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class InnerFieldsNameConverter implements AdvancedNameConverterInterface
{
    private $decorated;

    public function __construct(?NameConverterInterface $decorated = null)
    {
        $this->decorated = $decorated ?? new CamelCaseToSnakeCaseNameConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($propertyName, string $class = null, string $format = null, array $context = [])
    {
        return $this->convertInnerFields($propertyName, true, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($propertyName, string $class = null, string $format = null, array $context = [])
    {
        return $this->convertInnerFields($propertyName, false, $class, $format, $context);
    }

    private function convertInnerFields(string $propertyName, bool $normalization, string $class = null, string $format = null, $context = []): string
    {
        $convertedProperties = [];

        foreach (explode('.', $propertyName) as $decomposedProperty) {
            $convertedProperties[] = $this->decorated->{$normalization ? 'normalize' : 'denormalize'}($decomposedProperty, $class, $format, $context);
        }

        return implode('.', $convertedProperties);
    }
}
