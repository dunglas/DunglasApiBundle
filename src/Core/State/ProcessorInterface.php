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
 * Process data: send an email, persist to storage, add to queue etc.
 *
 * @experimental
 */
interface ProcessorInterface
{
    /**
     * Should we continue calling the next Processor or stop after this one?
     * Defaults to stop the ChainProcessor if this interface is not implemented.
     */
    public function resumable(array $context = []): bool;

    /**
     * Whether this state handler supports the class/identifier tuple.
     */
    public function supports($data, array $identifiers = [], array $context = []): bool;

    /**
     * Handle the state.
     */
    public function process($data, array $identifiers = [], array $context = []);
}
