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

/**
 * Retrieves information about a class.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ClassInfoTrait
{
    /**
     * Get class name of the given object.
     *
     * @param object $object
     */
    private function getObjectClass($object): string
    {
        return $this->getRealClassName(\get_class($object));
    }

    /**
     * Get the real class name of a class name that could be a proxy.
     */
    private function getRealClassName(string $className): string
    {
        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        $positionCg = strrpos($className, '\\__CG__\\');
        $positionPm = strrpos($className, '\\__PM__\\');

        if (false === $positionCg && false === $positionPm) {
            return $className;
        }

        if (false !== $positionCg) {
            return substr($className, $positionCg + 8);
        }

        $className = ltrim($className, '\\');

        return substr(
            $className,
            8 + $positionPm,
            strrpos($className, '\\') - ($positionPm + 8)
        );
    }
}
