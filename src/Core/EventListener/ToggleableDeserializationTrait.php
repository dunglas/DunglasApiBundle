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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * TODO: 3.0 remove this.
 *
 * @internal
 */
trait ToggleableDeserializationTrait
{
    use ToggleableOperationAttributeTrait;

    private function isRequestToDeserialize(Request $request, array $attributes): bool
    {
        return
            'DELETE' !== $request->getMethod()
            && !$request->isMethodSafe()
            && [] !== $attributes
            && $attributes['receive']
            && !$this->isOperationAttributeDisabled($attributes, DeserializeListener::OPERATION_ATTRIBUTE_KEY);
    }
}
