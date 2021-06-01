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
 * Tries each configured data provider and returns the result of the first able to handle the resource class.
 *
 * @experimental
 */
final class ChainProvider implements ProviderInterface
{
    /**
     * @var iterable<ProviderInterface>
     *
     * @internal
     */
    public $providers;

    /**
     * @param ProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function provide(string $resourceClass, array $identifiers = [], array $context = [])
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($resourceClass, $identifiers, $context)) {
                return $provider->provide($resourceClass, $identifiers, $context);
            }
        }

        return \count($identifiers) ? null : [];
    }

    public function supports(string $resourceClass, array $identifiers = [], array $context = []): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($resourceClass, $identifiers, $context)) {
                return true;
            }
        }

        return false;
    }
}
