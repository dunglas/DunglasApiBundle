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
 * Gets reflection classes for php files in the given directories.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @internal
 */
final class ReflectionClassRecursiveIterator
{
    private function __construct()
    {
    }

    public static function getReflectionClassesFromDirectories(array $directories): \Iterator
    {
        foreach ($directories as $path) {
            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+\.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (!preg_match('(^phar:)i', $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                try {
                    require_once $sourceFile;
                } catch (\Throwable $t) {
                    // invalid PHP file (example: missing parent class)
                    continue;
                }

                $includedFiles[$sourceFile] = true;
            }
        }

        $declared = array_merge(get_declared_classes(), get_declared_interfaces());
        foreach ($declared as $className) {
            $reflectionClass = new \ReflectionClass($className);
            $sourceFile = $reflectionClass->getFileName();
            if (isset($includedFiles[$sourceFile])) {
                yield $className => $reflectionClass;
            }
        }
    }
}
