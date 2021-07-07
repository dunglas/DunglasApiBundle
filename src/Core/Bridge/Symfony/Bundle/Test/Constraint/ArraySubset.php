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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint;

use PHPUnit\Runner\Version;
use PHPUnit\SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\ComparisonFailure as LegacyComparisonFailure;

if (!class_exists(ComparisonFailure::class)) {
    class_alias(LegacyComparisonFailure::class, 'PHPUnit\SebastianBergmann\Comparator\ComparisonFailure');
}

// Aliases as string to avoid loading the class
if (\PHP_VERSION_ID >= 80000 || (float) Version::series() >= 9) {
    class_alias('ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint\ArraySubsetV9', 'ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint\ArraySubset');
} else {
    class_alias('ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint\ArraySubsetLegacy', 'ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint\ArraySubset');
}
