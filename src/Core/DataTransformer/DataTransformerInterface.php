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

namespace ApiPlatform\Core\DataTransformer;

/**
 * Transforms a DTO or an Anonymous class to a Resource object.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface DataTransformerInterface
{
    /**
     * Transforms the given object to something else, usually another object.
     * This must return the original object if no transformations have been done.
     *
     * @param object $object
     *
     * @return object
     */
    public function transform($object, string $to, array $context = []);

    /**
     * Checks whether the transformation is supported for a given data and context.
     *
     * @param object|array $data object on normalize / array on denormalize
     */
    public function supportsTransformation($data, string $to, array $context = []): bool;
}
