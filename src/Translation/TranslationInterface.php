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

namespace ApiPlatform\Core\Translation;

/**
 * Represents a translation for a resource.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface TranslationInterface
{
    public function getTranslatableResource(): TranslatableInterface;

    public function getLocale(): string;

    public function setLocale(string $locale);
}
