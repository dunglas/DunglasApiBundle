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
 * Extracts formats for a given operation according to the retrieved Metadata.
 *
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 *
 * @deprecated since API Platform 2.5, use the "formats" attribute instead
 */
interface FormatsProviderInterface
{
    /**
     * Finds formats for an operation.
     */
    public function getFormatsFromAttributes(array $attributes): array;
}
