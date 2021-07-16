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
 * Guesses the error format to use.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ErrorFormatGuesser
{
    private function __construct()
    {
    }

    /**
     * Get the error format and its associated MIME type.
     */
    public static function guessErrorFormat(Request $request, array $errorFormats): array
    {
        $requestFormat = $request->getRequestFormat('');

        if ('' !== $requestFormat && isset($errorFormats[$requestFormat])) {
            return ['key' => $requestFormat, 'value' => $errorFormats[$requestFormat]];
        }

        $requestMimeTypes = Request::getMimeTypes($request->getRequestFormat());
        $defaultFormat = [];

        foreach ($errorFormats as $format => $errorMimeTypes) {
            if (array_intersect($requestMimeTypes, $errorMimeTypes)) {
                return ['key' => $format, 'value' => $errorMimeTypes];
            }

            if (!$defaultFormat) {
                $defaultFormat = ['key' => $format, 'value' => $errorMimeTypes];
            }
        }

        return $defaultFormat;
    }
}
