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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the context used by the Symfony Serializer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @deprecated since API Platform 2.6, use {@see \ApiPlatform\Core\Serializer\SerializerContextFactoryInterface} interface instead
 */
interface SerializerContextBuilderInterface
{
    /**
     * Creates a serialization context from a Request.
     *
     * @throws RuntimeException
     *
     * @deprecated since API Platform 2.6, use {@see \ApiPlatform\Core\Serializer\SerializerContextFactoryInterface::create()} method instead
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array;
}
