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

namespace ApiPlatform\Core\Api;

/**
 * Matches a mime type to a format.
 *
 * @internal
 */
final class FormatMatcher
{
    /**
     * @var array<string, string[]>
     */
    private $formats;

    /**
     * @param array<string, string[]|string> $formats
     */
    public function __construct(array $formats)
    {
        $normalizedFormats = [];
        foreach ($formats as $format => $mimeTypes) {
            $normalizedFormats[$format] = (array) $mimeTypes;
        }
        $this->formats = $normalizedFormats;
    }

    /**
     * Gets the format associated with the mime type.
     *
     * Adapted from {@see \Symfony\Component\HttpFoundation\Request::getFormat}.
     */
    public function getFormat(string $mimeType): ?string
    {
        $canonicalMimeType = null;
        $pos = strpos($mimeType, ';');
        if (false !== $pos) {
            $canonicalMimeType = trim(substr($mimeType, 0, $pos));
        }

        foreach ($this->formats as $format => $mimeTypes) {
            if (\in_array($mimeType, $mimeTypes, true)) {
                return $format;
            }
            if (null !== $canonicalMimeType && \in_array($canonicalMimeType, $mimeTypes, true)) {
                return $format;
            }
        }

        return null;
    }
}
