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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DataPersister;

use ApiPlatform\Core\DataPersister\ChainDataPersister;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class TraceableChainDataPersister implements ContextAwareDataPersisterInterface
{
    private $persisters = [];
    private $persistersResponse = [];
    private $decorated;

    public function __construct(DataPersisterInterface $dataPersister)
    {
        if ($dataPersister instanceof ChainDataPersister) {
            $this->decorated = $dataPersister;
            $this->persisters = $dataPersister->persisters;
        }
    }

    public function getPersistersResponse(): array
    {
        return $this->persistersResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        return $this->decorated->supports($data, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = [])
    {
        $this->tracePersisters($data, $context);

        return $this->decorated->persist($data, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = [])
    {
        $this->tracePersisters($data, $context);

        return $this->decorated->remove($data, $context);
    }

    private function tracePersisters($data, array $context = [])
    {
        $found = false;
        foreach ($this->persisters as $persister) {
            if (
                ($this->persistersResponse[\get_class($persister)] = $found ? false : $persister->supports($data, $context))
                &&
                !($persister instanceof ResumableDataPersisterInterface && $persister->resumable()) && !$found
            ) {
                $found = true;
            }
        }
    }
}
