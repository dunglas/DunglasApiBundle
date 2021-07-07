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

namespace ApiPlatform\Core\Util;

use Doctrine\Common\Inflector\Inflector as LegacyInflector;
use Doctrine\Inflector\Inflector as InflectorObject;
use Doctrine\Inflector\InflectorFactory;

/**
 * Facade for Doctrine Inflector.
 *
 * This class allows us to maintain compatibility with Doctrine Inflector 1.3 and 2.0 at the same time.
 *
 * @internal
 */
final class Inflector
{
    /**
     * @var InflectorObject|null
     */
    private static $instance;

    private static function getInstance(): InflectorObject
    {
        return self::$instance
            ?? self::$instance = InflectorFactory::create()->build();
    }

    /**
     * @see InflectorObject::tableize()
     */
    public static function tableize(string $word): string
    {
        return class_exists(InflectorFactory::class) ? self::getInstance()->tableize($word) : LegacyInflector::tableize($word); // @phpstan-ignore-line
    }

    /**
     * @see InflectorObject::pluralize()
     */
    public static function pluralize(string $word): string
    {
        return class_exists(InflectorFactory::class) ? self::getInstance()->pluralize($word) : LegacyInflector::pluralize($word); // @phpstan-ignore-line
    }
}
