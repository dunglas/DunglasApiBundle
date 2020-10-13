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

namespace ApiPlatform\Core\Inflector;

use Doctrine\Common\Inflector\Inflector as LegacyInflector;
use Symfony\Component\Inflector\Inflector as SymfonyInflector;
use Symfony\Component\String\UnicodeString;

/**
 * @internal
 *
 * @deprecated removed in version 3.0
 */
final class Inflector
{
    public static function tableize(string $word): string
    {
        if (class_exists(UnicodeString::class)) {
            return (string) (new UnicodeString($word))->snake();
        }
        @trigger_error('Using the Doctrine inflector is deprecated since API Platform 2.7 and will not be supported anymore in 3.0, use the Symfony string component instead.',
            E_USER_DEPRECATED);

        return LegacyInflector::tableize($word);
    }

    public static function pluralize(string $singular): string
    {
        if (class_exists(SymfonyInflector::class)) {
            $singularization = SymfonyInflector::singularize($singular);
            $pluralization = SymfonyInflector::pluralize(\is_array($singularization) ? end($singularization) : $singularization);

            return \is_array($pluralization) ? end($pluralization) : $pluralization;
        }
        @trigger_error('Using the Doctrine inflector is deprecated since API Platform 2.7 and will not be supported anymore in 3.0, use the Symfony string component instead.',
            E_USER_DEPRECATED);

        return LegacyInflector::pluralize($singular);
    }
}
