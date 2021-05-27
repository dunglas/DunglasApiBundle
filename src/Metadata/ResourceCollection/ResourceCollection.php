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

namespace ApiPlatform\Core\Metadata\ResourceCollection;

use ApiPlatform\Metadata\Resource;

/**
 * @experimental
 * @extends \ArrayObject<int, Resource>
 */
final class ResourceCollection extends \ArrayObject
{
    private $metadataCache = [];
    private $operationCache = [];

    public function getOperation(string $method, string $uriTemplate)
    {
        if (isset($this->operationCache[$uriTemplate][$method])) {
            return $this->operationCache[$uriTemplate][$method];
        }

        if (!($resourceMetadata = $this->getResource($uriTemplate))) {
            return null;
        }

        foreach ($resourceMetadata->operations as $operation) {
            if ($operation->method === $method) {
                if (!isset($this->operationCache[$uriTemplate])) {
                    $this->operationCache[$uriTemplate] = [];
                }

                return $this->operationCache[$uriTemplate][$method] = $operation;
            }
        }

        return null;
    }

    public function getResource(string $uriTemplate): ?Resource
    {
        if (isset($this->metadataCache[$uriTemplate])) {
            return $this->metadataCache[$uriTemplate];
        }

        $it = $this->getIterator();

        while ($it->valid()) {
            /** @var resource */
            $metadata = $it->current();

            if ($metadata->uriTemplate === $uriTemplate) {
                $this->metadataCache[$uriTemplate] = $metadata;

                return $metadata;
            }

            $it->next();
        }

        return null;
    }
}
