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

namespace ApiPlatform\Core\Identifier;

/**
 * Gives access to the context in the IdentifierConverter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface ContextAwareIdentifierConverterInterface extends IdentifierConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($data, string $class, array $context = []): array;
}
