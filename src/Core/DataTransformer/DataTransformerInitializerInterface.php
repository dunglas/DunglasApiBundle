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

interface DataTransformerInitializerInterface extends DataTransformerInterface
{
    /**
     * Creates a new DTO object that the data will then be serialized into (using object_to_populate).
     *
     * This is useful to "initialize" the DTO object based on the current resource's data.
     *
     * @return object|null
     */
    public function initialize(string $inputClass, array $context = []);
}
