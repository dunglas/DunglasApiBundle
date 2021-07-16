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
 * Chained data persisters.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ChainDataPersister implements ContextAwareDataPersisterInterface
{
    /**
     * @var iterable<DataPersisterInterface>
     *
     * @internal
     */
    public $persisters;

    /**
     * @param DataPersisterInterface[] $persisters
     */
    public function __construct(iterable $persisters)
    {
        $this->persisters = $persisters;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        foreach ($this->persisters as $persister) {
            if ($persister->supports($data, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = [])
    {
        foreach ($this->persisters as $persister) {
            if ($persister->supports($data, $context)) {
                $data = $persister->persist($data, $context) ?? $data;
                if ($persister instanceof ResumableDataPersisterInterface && $persister->resumable($context)) {
                    continue;
                }

                return $data;
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = [])
    {
        foreach ($this->persisters as $persister) {
            if ($persister->supports($data, $context)) {
                $persister->remove($data, $context);
                if ($persister instanceof ResumableDataPersisterInterface && $persister->resumable($context)) {
                    continue;
                }

                return;
            }
        }
    }
}
