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

namespace ApiPlatform\Core\Stage;

/**
 * Retrieves data from the applicable data provider.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface ReadStageInterface
{
    public const OPERATION_ATTRIBUTE_KEY = 'read';

    /**
     * @return object|iterable|null
     */
    public function apply(array $attributes, array $parameters, ?array $filters, string $method, array $normalizationContext);
}
