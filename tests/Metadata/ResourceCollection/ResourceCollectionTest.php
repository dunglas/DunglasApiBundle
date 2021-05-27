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

namespace ApiPlatform\Core\Tests\Metadata\ResourceCollection;

use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

class ResourceCollectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testResourceCollection()
    {
        $operation = new Get();
        $resource = new Resource(uriTemplate: '/dummies/{id}', operations: [$operation]);
        $resourceCollection = new ResourceCollection([$resource]);

        $this->assertEquals($resource, $resourceCollection->getResource('/dummies/{id}'));
        $this->assertEquals($resource, $resourceCollection->getResource('/dummies/{id}'));
        $this->assertEquals($operation, $resourceCollection->getOperation('GET', '/dummies/{id}'));
        $this->assertEquals($operation, $resourceCollection->getOperation('GET', '/dummies/{id}'));
    }
}
