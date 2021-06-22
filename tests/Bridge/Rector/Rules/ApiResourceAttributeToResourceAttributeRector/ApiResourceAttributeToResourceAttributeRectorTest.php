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

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector;

use Iterator;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

/*
 * @requires PHP 8.0
 */
if (class_exists(AbstractRectorTestCase::class)) {
    class ApiResourceAttributeToResourceAttributeRectorTest extends AbstractRectorTestCase
    {
        /**
         * @dataProvider provideData()
         */
        public function test(SmartFileInfo $fileInfo): void
        {
            $this->doTestFileInfo($fileInfo);
        }

        /**
         * @return Iterator<SmartFileInfo>
         */
        public function provideData(): Iterator
        {
            return $this->yieldFilesFromDirectory(__DIR__.'/Fixture');
        }

        public function provideConfigFilePath(): string
        {
            return __DIR__.'/config/configured_rule.php';
        }
    }
}
