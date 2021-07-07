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

namespace ApiPlatform\Core\OpenApi\Factory;

use ApiPlatform\Core\OpenApi\OpenApi;

interface OpenApiFactoryInterface
{
    /**
     * Creates an OpenApi class.
     */
    public function __invoke(array $context = []): OpenApi;
}
