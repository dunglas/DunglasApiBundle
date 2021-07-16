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

use ApiPlatform\Core\Metadata\Resource\Factory\AnnotationResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AnnotationResourceNameCollectionFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    public function testCreate()
    {
        $decorated = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $decorated->create()->willReturn(new ResourceNameCollection(['foo', 'bar']))->shouldBeCalled();

        $reader = $this->prophesize(Reader::class);

        $metadata = new AnnotationResourceNameCollectionFactory($reader->reveal(), [], $decorated->reveal());

        $this->assertEquals(new ResourceNameCollection(['foo', 'bar']), $metadata->create());
    }

    public function testCreateAttribute()
    {
        $decorated = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $decorated->create()->willReturn(new ResourceNameCollection(['foo', 'bar']))->shouldBeCalled();

        $metadata = new AnnotationResourceNameCollectionFactory(null, [], $decorated->reveal());
        $this->assertEquals(new ResourceNameCollection(['foo', 'bar']), $metadata->create());
    }
}
