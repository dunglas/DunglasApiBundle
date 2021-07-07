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

namespace ApiPlatform\State;

/**
 * Tries each configured data processors and returns the result of the first able to handle the resource class.
 *
 * @experimental
 */
final class ChainProcessor implements ProcessorInterface
{
    /**
     * @var iterable<ProcessorInterface>
     *
     * @internal
     */
    public $processors;

    /**
     * @param ProcessorInterface[] $processors
     */
    public function __construct(iterable $processors)
    {
        $this->processors = $processors;
    }

    public function resumable(?string $operationName = null, array $context = []): bool
    {
        foreach ($this->processors as $processor) {
            if ($processor->resumable($operationName, $context)) {
                return $processor->resumable($operationName, $context);
            }
        }

        return false;
    }

    public function supports($data, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        foreach ($this->processors as $processor) {
            if ($supports = $processor->supports($data, $identifiers, $operationName, $context)) {
                return $supports;
            }
        }

        return false;
    }

    public function process($data, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($data, $identifiers, $operationName, $context)) {
                return $processor->process($data, $identifiers, $operationName, $context);
            }
        }

        return false;
    }
}
