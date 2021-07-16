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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

/**
 * {@inheritdoc}
 *
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 *
 * @deprecated since API Platform 2.5, use the "formats" attribute instead
 */
final class FormatsProvider implements FormatsProviderInterface, OperationAwareFormatsProviderInterface
{
    private $configuredFormats;
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, array $configuredFormats)
    {
        @trigger_error(sprintf('The "%s" class is deprecated since API Platform 2.5, use the "formats" attribute instead.', __CLASS__), \E_USER_DEPRECATED);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->configuredFormats = $configuredFormats;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function getFormatsFromAttributes(array $attributes): array
    {
        if (!$attributes || !isset($attributes['resource_class'])) {
            return $this->configuredFormats;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

        if (!$formats = $resourceMetadata->getOperationAttribute($attributes, 'formats', [], true)) {
            return $this->configuredFormats;
        }

        if (!\is_array($formats)) {
            throw new InvalidArgumentException(sprintf("The 'formats' attributes must be an array, %s given for resource class '%s'.", \gettype($formats), $attributes['resource_class']));
        }

        return $this->getOperationFormats($formats);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function getFormatsFromOperation(string $resourceClass, string $operationName, string $operationType): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (!$formats = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'formats', [], true)) {
            return $this->configuredFormats;
        }

        if (!\is_array($formats)) {
            throw new InvalidArgumentException(sprintf("The 'formats' attributes must be an array, %s given for resource class '%s'.", \gettype($formats), $resourceClass));
        }

        return $this->getOperationFormats($formats);
    }

    /**
     * Filter and populate the acceptable formats.
     *
     * @throws InvalidArgumentException
     */
    private function getOperationFormats(array $annotationFormats): array
    {
        $resourceFormats = [];
        foreach ($annotationFormats as $format => $value) {
            if (!is_numeric($format)) {
                $resourceFormats[$format] = (array) $value;
                continue;
            }
            if (!\is_string($value)) {
                throw new InvalidArgumentException(sprintf("The 'formats' attributes value must be a string when trying to include an already configured format, %s given.", \gettype($value)));
            }
            if (\array_key_exists($value, $this->configuredFormats)) {
                $resourceFormats[$value] = $this->configuredFormats[$value];
                continue;
            }

            throw new InvalidArgumentException(sprintf("You either need to add the format '%s' to your project configuration or declare a mime type for it in your annotation.", $value));
        }

        return $resourceFormats;
    }
}
