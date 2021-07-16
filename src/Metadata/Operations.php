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

namespace ApiPlatform\Metadata;

/**
 * @internal
 *
 * @extends array<string, Operation>
 */
final class Operations implements \IteratorAggregate, \Countable
{
    private $operations;

    /**
     * @param Operation[] $operations
     */
    public function __construct(array $operations = [])
    {
        $this->operations = [];
        foreach ($operations as $operationName => $operation) {
            $this->operations[] = [$operationName, $operation];
        }

        usort($this->operations, fn ($a, $b) => $a[1]->getPriority() - $b[1]->getPriority());
    }

    public function getIterator(): \Traversable
    {
        return (function () {
            foreach ($this->operations as [$operationName, $operation]) {
                yield $operationName => $operation;
            }
        })();
    }

    public function count(): int
    {
        return \count($this->operations);
    }
}
