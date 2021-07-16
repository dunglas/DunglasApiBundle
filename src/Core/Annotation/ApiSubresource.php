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

namespace ApiPlatform\Core\Annotation;

/**
 * ApiSubresource annotation.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 * @Attributes(
 *     @Attribute("maxDepth", type="int"),
 * )
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ApiSubresource
{
    /**
     * @var int
     */
    public $maxDepth;

    /**
     * @param int $maxDepth
     */
    public function __construct($maxDepth = null)
    {
        if (!\is_array($maxDepth)) { // @phpstan-ignore-line
            $this->maxDepth = $maxDepth;
        } else {
            $this->maxDepth = $maxDepth['maxDepth'] ?? null;
        }
    }
}
