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

namespace ApiPlatform\Core\DataProvider;

trait RestrictDataProviderTrait
{
    /** @internal */
    public $dataProviders = [];

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        foreach ($this->dataProviders as $dataProvider) {
            if ($dataProvider instanceof RestrictedDataProviderInterface
                && $dataProvider->supports($resourceClass, $operationName, $context)) {
                return true;
            }
        }

        return false;
    }
}
