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

namespace ApiPlatform\Core\Tests\JsonLd;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\JsonLd\ContextBuilder;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTranslatable;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Core\Translation\ResourceTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Markus Mächler <markus.maechler@bithost.ch>
 */
class ContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    private $entityClass;
    private $resourceNameCollectionFactoryProphecy;
    private $resourceMetadataFactoryProphecy;
    private $propertyNameCollectionFactoryProphecy;
    private $propertyMetadataFactoryProphecy;
    private $urlGeneratorProphecy;

    protected function setUp(): void
    {
        $this->entityClass = '\Dummy\DummyEntity';
        $this->resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $this->propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
    }

    public function testResourceContext()
    {
        $this->resourceMetadataFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadata('DummyEntity'));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, []));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => 'DummyEntity/dummyPropertyA',
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testIriOnlyResourceContext()
    {
        $this->resourceMetadataFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadata('DummyEntity', null, null, null, null, ['normalization_context' => ['iri_only' => true]]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'hydra:member' => [
                '@type' => '@id',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testResourceContextWithJsonldContext()
    {
        $this->resourceMetadataFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadata('DummyEntity'));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, ['jsonld_context' => ['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']]));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@type' => '@id',
                '@id' => 'customId',
                'foo' => 'bar',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testGetEntryPointContext()
    {
        $this->resourceMetadataFactoryProphecy->create('dummyPropertyA')->willReturn(new ResourceMetadata('DummyEntity'));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, ['jsonld_context' => ['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']]));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyEntity' => [
                '@type' => '@id',
                '@id' => 'Entrypoint/dummyEntity',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getEntrypointContext());
    }

    public function testResourceContextWithReverse()
    {
        $this->resourceMetadataFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadata('DummyEntity'));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, ['jsonld_context' => ['@reverse' => 'parent']]));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@id' => 'DummyEntity/dummyPropertyA',
                '@reverse' => 'parent',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testResourceContextWithLanguage()
    {
        $resourceClass = DummyTranslatable::class;

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata('DummyTranslatable'));
        $this->propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection([]));
        $requestStack = new RequestStack();
        $request = new Request();
        $request->setLocale('fr');
        $requestStack->push($request);
        $resourceTranslator = new ResourceTranslator($requestStack, PropertyAccess::createPropertyAccessor(), $this->resourceMetadataFactoryProphecy->reveal());

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), null, $resourceTranslator);

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            '@language' => 'fr',
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($resourceClass));
    }

    public function testResourceContextWithAllTranslationsEnabled()
    {
        $resourceClass = DummyTranslatable::class;

        $resourceMetadata = new ResourceMetadata('DummyTranslatable');
        $resourceMetadata = $resourceMetadata->withAttributes(['translation' => ['allTranslationsEnabled' => true]]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);
        $this->propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($resourceClass, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true));
        $resourceTranslator = new ResourceTranslator(new RequestStack(), PropertyAccess::createPropertyAccessor(), $this->resourceMetadataFactoryProphecy->reveal());

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), null, $resourceTranslator);

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@container' => '@language',
                '@id' => 'DummyTranslatable/dummyPropertyA',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($resourceClass));
    }

    public function testAnonymousResourceContext()
    {
        $dummy = new Dummy();
        $this->propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, []));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $iri = '_:'.(\function_exists('spl_object_id') ? spl_object_id($dummy) : spl_object_hash($dummy));
        $expected = [
            '@context' => [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'Dummy/dummyPropertyA',
            ],
            '@id' => $iri,
            '@type' => 'Dummy',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($dummy));
    }

    public function testAnonymousResourceContextWithIri()
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, []));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'OutputDto/dummyPropertyA',
            ],
            '@id' => '/dummies',
            '@type' => 'OutputDto',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy']));
    }

    public function testAnonymousResourceContextWithApiResource()
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, []));

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy'));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'OutputDto/dummyPropertyA',
            ],
            '@id' => '/dummies',
            '@type' => 'Dummy',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy', 'api_resource' => new Dummy()]));
    }

    public function testAnonymousResourceContextWithApiResourceHavingContext()
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'Dummy property A', true, true, true, true, false, false, null, null, []));

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy'));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@id' => '/dummies',
            '@type' => 'Dummy',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy', 'api_resource' => new Dummy(), 'has_context' => true]));
    }
}
