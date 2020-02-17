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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\DirectoryResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

class DirectoryResourceMetadataFactoryTest extends TestCase
{
    /**
     * @dataProvider getCreateDependencies
     */
    public function testCreate(array $defaults, $decorated, string $expectedShortName, string $expectedDescription)
    {
        $factory = new DirectoryResourceMetadataFactory($defaults, $decorated ? $decorated->reveal() : null);
        $metadata = $factory->create(Dummy::class);

        $this->assertEquals($expectedShortName, $metadata->getShortName());
        $this->assertEquals($expectedDescription, $metadata->getDescription());
    }

    public function getCreateDependencies()
    {
        $decoratedThrow = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedThrow->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);
        $decoratedReturn = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedReturn->create(Dummy::class)->willReturn(new ResourceMetadata('hello', 'blabla'))->shouldBeCalled();

        return [
            [[], $decoratedReturn, 'hello', 'blabla'],
        ];
    }
}
