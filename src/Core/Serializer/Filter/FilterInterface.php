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

namespace ApiPlatform\Core\Serializer\Filter;

use ApiPlatform\Core\Api\FilterInterface as BaseFilterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Symfony serializer context builder filter interface.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
interface FilterInterface extends BaseFilterInterface
{
    /**
     * Apply a filter to the serializer context.
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context);
}
