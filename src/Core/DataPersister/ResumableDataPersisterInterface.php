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

namespace ApiPlatform\Core\DataPersister;

/**
 * Control the resumability of the data persister chain.
 */
interface ResumableDataPersisterInterface
{
    /**
     * Should we continue calling the next DataPersister or stop after this one?
     * Defaults to stop the ChainDatapersister if this interface is not implemented.
     */
    public function resumable(array $context = []): bool;
}
