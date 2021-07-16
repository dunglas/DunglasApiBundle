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

namespace ApiPlatform\Core\JsonLd;

use ApiPlatform\Core\Api\UrlGeneratorInterface;

/**
 * JSON-LD context builder with Input Output DTO support interface.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface AnonymousContextBuilderInterface extends ContextBuilderInterface
{
    /**
     * Creates a JSON-LD context based on the given object.
     * Usually this is used with an Input or Output DTO object.
     */
    public function getAnonymousResourceContext($object, array $context = [], int $referenceType = UrlGeneratorInterface::ABS_PATH): array;
}
