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

namespace ApiPlatform\Core\Tests\HttpCache;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\HttpCache\PurgerInterface;
use ApiPlatform\Core\HttpCache\VarnishClearer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Person;

/**
 * @author Florent Mata <florentmata@gmail.com>
 */
class VarnishClearerTest extends \PHPUnit_Framework_TestCase
{
    public function testClear()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([Foo::class, Person::class]))->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata('Foo'))->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(Person::class)->willReturn(new ResourceMetadata('Person'))->shouldBeCalled();

        $iris = [];
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $iri = IriConverter::IRI_INTERNAL_PREFIX.'/docs';
        $iriConverterProphecy->getApiDocIri()->willReturn($iri)->shouldBeCalled();
        $iris[$iri] = $iri;

        foreach (['Entrypoint', 'ConstraintViolationList', 'Error', 'Foo', 'Person'] as $shortName) {
            $iri = sprintf('%s/contexts/%s', IriConverter::IRI_INTERNAL_PREFIX, $shortName);
            $iriConverterProphecy->getContextIriFromShortName($shortName)->willReturn($iri)->shouldBeCalled();
            $iris[$iri] = $iri;
        }

        $purger = $this->prophesize(PurgerInterface::class);
        $purger->purge($iris)->shouldBeCalled();

        $clearer = new VarnishClearer($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $purger->reveal());
        $clearer->clear('fake/dir');
    }
}
