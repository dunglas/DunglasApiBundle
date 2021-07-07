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

namespace ApiPlatform\Core\Serializer\Filter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Property filter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class PropertyFilter implements FilterInterface
{
    private $overrideDefaultProperties;
    private $parameterName;
    private $whitelist;
    private $nameConverter;

    public function __construct(string $parameterName = 'properties', bool $overrideDefaultProperties = false, array $whitelist = null, NameConverterInterface $nameConverter = null)
    {
        $this->overrideDefaultProperties = $overrideDefaultProperties;
        $this->parameterName = $parameterName;
        $this->whitelist = null === $whitelist ? null : $this->formatWhitelist($whitelist);
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context)
    {
        if (null !== $propertyAttribute = $request->attributes->get('_api_filter_property')) {
            $properties = $propertyAttribute;
        } elseif (\array_key_exists($this->parameterName, $commonAttribute = $request->attributes->get('_api_filters', []))) {
            $properties = $commonAttribute[$this->parameterName];
        } else {
            $properties = $request->query->all()[$this->parameterName] ?? null;
        }

        if (!\is_array($properties)) {
            return;
        }

        $properties = $this->denormalizeProperties($properties);

        if (null !== $this->whitelist) {
            $properties = $this->getProperties($properties, $this->whitelist);
        }

        if (!$this->overrideDefaultProperties && isset($context[AbstractNormalizer::ATTRIBUTES])) {
            $properties = array_merge_recursive((array) $context[AbstractNormalizer::ATTRIBUTES], $properties);
        }

        $context[AbstractNormalizer::ATTRIBUTES] = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $example = sprintf('%1$s[]={propertyName}&%1$s[]={anotherPropertyName}&%1$s[{nestedPropertyParent}][]={nestedProperty}',
            $this->parameterName
        );

        return [
            "$this->parameterName[]" => [
                'property' => null,
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
                'swagger' => [
                    'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: '.$example,
                    'name' => "$this->parameterName[]",
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'openapi' => [
                    'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: '.$example,
                    'name' => "$this->parameterName[]",
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate an array of whitelist properties to match the format that properties
     * will have in the request.
     *
     * @param array $whitelist the whitelist to format
     *
     * @return array An array containing the whitelist ready to match request parameters
     */
    private function formatWhitelist(array $whitelist): array
    {
        if (array_values($whitelist) === $whitelist) {
            return $whitelist;
        }
        foreach ($whitelist as $name => $value) {
            if (null === $value) {
                unset($whitelist[$name]);
                $whitelist[] = $name;
            }
        }

        return $whitelist;
    }

    private function getProperties(array $properties, array $whitelist = null): array
    {
        $whitelist = $whitelist ?? $this->whitelist;
        $result = [];

        foreach ($properties as $key => $value) {
            if (is_numeric($key)) {
                if (\in_array($propertyName = $this->denormalizePropertyName($value), $whitelist, true)) {
                    $result[] = $propertyName;
                }

                continue;
            }

            if (\is_array($value) && isset($whitelist[$key]) && $recursiveResult = $this->getProperties($value, $whitelist[$key])) {
                $result[$this->denormalizePropertyName($key)] = $recursiveResult;
            }
        }

        return $result;
    }

    private function denormalizeProperties(array $properties): array
    {
        if (null === $this->nameConverter || !$properties) {
            return $properties;
        }

        $result = [];
        foreach ($properties as $key => $value) {
            $result[$this->denormalizePropertyName((string) $key)] = \is_array($value) ? $this->denormalizeProperties($value) : $this->denormalizePropertyName($value);
        }

        return $result;
    }

    private function denormalizePropertyName($property)
    {
        return null !== $this->nameConverter ? $this->nameConverter->denormalize($property) : $property;
    }
}
