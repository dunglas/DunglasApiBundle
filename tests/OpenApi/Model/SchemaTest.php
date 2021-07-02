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

namespace ApiPlatform\Core\Tests\OpenApi\Model;

use ApiPlatform\Core\OpenApi\Model\Schema;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class SchemaTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testLegacySchema()
    {
        $this->expectDeprecation('The nullable keyword has been removed from the Schema Object (null can be used as a type value). This behaviour will not be possible anymore in API Platform 3.0.');

        $schema = new Schema(true);
    }
}
