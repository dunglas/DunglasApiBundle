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

namespace ApiPlatform\Core\Operation;

use ApiPlatform\Core\Util\Inflector;

/**
 * Generate a path name with a dash separator according to a string and whether it's a collection or not.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DashPathSegmentNameGenerator implements PathSegmentNameGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        return $collection ? $this->dashize(Inflector::pluralize($name)) : $this->dashize($name);
    }

    private function dashize(string $string): string
    {
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '-$1', $string));
    }
}
