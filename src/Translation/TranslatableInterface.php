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

use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * Indicates that the resource can be translated.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface TranslatableInterface
{
    /**
     * Returns null if there is no translation for the given locale.
     *
     * @Ignore
     */
    public function getResourceTranslation(string $locale): ?TranslationInterface;

    /**
     * @return TranslationInterface[]|iterable<TranslationInterface>
     *
     * @Ignore
     */
    public function getResourceTranslations(): iterable;

    public function addResourceTranslation(TranslationInterface $translation): void;

    public function removeResourceTranslation(TranslationInterface $translation): void;
}
