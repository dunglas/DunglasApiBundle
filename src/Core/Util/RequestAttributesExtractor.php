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

namespace ApiPlatform\Core\Util;

use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts data used by the library form a Request instance.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RequestAttributesExtractor
{
    private function __construct()
    {
    }

    /**
     * Extracts resource class, operation name and format request attributes. Returns an empty array if the request does
     * not contain required attributes.
     */
    public static function extractAttributes(Request $request): array
    {
        return AttributesExtractor::extractAttributes($request->attributes->all());
    }
}
