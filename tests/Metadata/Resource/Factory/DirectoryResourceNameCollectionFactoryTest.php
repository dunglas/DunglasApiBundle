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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\DirectoryResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Util\ReflectionClassRecursiveIterator;
use PHPUnit\Framework\TestCase;

class DirectoryResourceNameCollectionFactoryTest extends TestCase
{
    public function testCreate()
    {
        $decorated = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $decorated->create()->willReturn(new ResourceNameCollection([]))->shouldBeCalled();
        $metadata = new DirectoryResourceNameCollectionFactory([
            __DIR__.'/../../../Fixtures/TestBundle/Entity',
        ], $decorated->reveal());

        $listCollection = array_values(array_map(function (\ReflectionClass $namespace) {
            return $namespace->getName();
        },
            iterator_to_array(ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories([__DIR__.'/../../../Fixtures/TestBundle/Entity']))
        ));
        $this->assertEquals(new ResourceNameCollection($listCollection), $metadata->create());
    }
}
