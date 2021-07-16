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

namespace ApiPlatform\Core\Metadata\Resource;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal
 *
 * @deprecated
 */
trait ResourceToResourceMetadataTrait
{
    private $camelCaseToSnakeCaseNameConverter;

    private function transformResourceToResourceMetadata(ApiResource $resource): ResourceMetadata
    {
        $collectionOperations = [];
        $itemOperations = [];
        foreach ($resource->getOperations() as $name => $operation) {
            $operation = $this->toArray($operation);

            if (!isset($operation['openapi_context'])) {
                $operation['openapi_context'] = [];
            }

            $operation['openapi_context']['operationId'] = $name;

            if (!($operation['identifiers'] ?? [])) {
                $collectionOperations[$name] = $operation;
                continue;
            }

            $itemOperations[$name] = $operation;
        }
        $attributes = $this->toArray($resource);

        $graphql = $resource->getGraphQl() ? $this->toArray($resource->getGraphQl()) : null;

        return new ResourceMetadata($resource->getShortName(), $resource->getDescription(), $resource->getTypes()[0] ?? null, $itemOperations, $collectionOperations, $attributes, null, $graphql);
    }

    private function toArray($object): array
    {
        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        $arr = [];
        foreach (get_class_methods($object) as $methodName) {
            if ('getOperations' === $methodName || 0 !== strpos($methodName, 'get')) {
                continue;
            }

            if (!$value = $object->{$methodName}()) {
                continue;
            }

            $arr[$this->camelCaseToSnakeCaseNameConverter->normalize(lcfirst(substr($methodName, 3)))] = $value;
        }

        return $arr;
    }
}
