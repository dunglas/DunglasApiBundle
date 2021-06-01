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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource;

/**
 * @experimental
 * @extends \ArrayObject<int, Resource>
 */
final class ResourceCollection extends \ArrayObject
{
    private $operationCache = [];

    public function getOperation(string $operationName): ?Operation
    {
        if (isset($this->operationCache[$operationName])) {
            return $this->operationCache[$operationName];
        }

        $it = $this->getIterator();

        while ($it->valid()) {
            /** @var resource */
            $metadata = $it->current();

            foreach ($metadata->operations as $name => $operation) {
                if ($name === $operationName) {
                    return $operation;
                }
            }

            $it->next();
        }

        return null;
    }
}
