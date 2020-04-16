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

namespace ApiPlatform\Core\Tests\Bridge\NelmioApiDoc\Parser;

use ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\UnknownDummy;
use Doctrine\Common\Collections\Collection;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @group legacy
 */
class ApiPlatformParserTest extends TestCase
{
    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testConstruct()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertInstanceOf(ParserInterface::class, $apiPlatformParser);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testSupports()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertTrue($apiPlatformParser->supports([
            'class' => sprintf('%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class),
        ]));
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testNoOnDataFirstArray()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertFalse($apiPlatformParser->supports([
            'class' => sprintf('%s', ApiPlatformParser::OUT_PREFIX),
        ]));
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testSupportsAttributeNormalization()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Acme\CustomAttributeDummy')->willReturn(new ResourceMetadata('dummy', 'dummy', null, [
            'get' => ['method' => 'GET', 'normalization_context' => [AbstractNormalizer::GROUPS => ['custom_attr_dummy_get']]],
            'put' => ['method' => 'PUT', 'denormalization_context' => [AbstractNormalizer::GROUPS => ['custom_attr_dummy_put']]],
            'delete' => ['method' => 'DELETE'],
        ], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create('Acme\CustomAttributeDummy', Argument::cetera())->willReturn(new PropertyNameCollection([
            'id',
            'name',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $idPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_INT, false))
            ->withDescription('The id.')
            ->withReadable(true)
            ->withWritable(false)
            ->withRequired(true);
        $propertyMetadataFactoryProphecy->create('Acme\CustomAttributeDummy', 'id')->willReturn($idPropertyMetadata)->shouldBeCalled();
        $namePropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_STRING, false))
            ->withDescription('The dummy name.')
            ->withReadable(true)
            ->withWritable(true)
            ->withRequired(true);
        $propertyMetadataFactoryProphecy->create('Acme\CustomAttributeDummy', 'name')->willReturn($namePropertyMetadata)->shouldBeCalled();

        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $actual = $apiPlatformParser->parse([
            'class' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, 'Acme\CustomAttributeDummy', 'get'),
        ]);

        $this->assertEquals([
            'id' => [
                'dataType' => DataTypes::INTEGER,
                'required' => true,
                'description' => 'The id.',
                'readonly' => true,
            ],
            'name' => [
                'dataType' => DataTypes::STRING,
                'required' => true,
                'description' => 'The dummy name.',
                'readonly' => false,
            ],
        ], $actual);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testSupportsUnknownResource()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(UnknownDummy::class)->willThrow(ResourceClassNotFoundException::class)->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertFalse($apiPlatformParser->supports([
            'class' => sprintf('%s:%s', ApiPlatformParser::OUT_PREFIX, UnknownDummy::class),
        ]));
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testSupportsUnsupportedClassFormat()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Argument::any())->shouldNotBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertFalse($apiPlatformParser->supports([
            'class' => Dummy::class,
        ]));
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testParse()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy', 'dummy', null, [
            'get' => ['method' => 'GET', 'normalization_context' => [AbstractNormalizer::GROUPS => ['custom_attr_dummy_get']]],
            'put' => ['method' => 'PUT', 'denormalization_context' => [AbstractNormalizer::GROUPS => ['custom_attr_dummy_put']]],
            'gerard' => ['method' => 'get', 'path' => '/gerard', 'denormalization_context' => [AbstractNormalizer::GROUPS => ['custom_attr_dummy_put']]],
            'delete' => ['method' => 'DELETE'],
        ], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::cetera())->willReturn(new PropertyNameCollection([
            'id',
            'name',
            'dummyPrice',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $idPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_INT, false))
            ->withDescription('The id.')
            ->withReadable(true)
            ->withWritable(false)
            ->withRequired(true);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->willReturn($idPropertyMetadata)->shouldBeCalled();
        $namePropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_STRING, false))
            ->withDescription('The dummy name.')
            ->withReadable(true)
            ->withWritable(true)
            ->withRequired(true);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->willReturn($namePropertyMetadata)->shouldBeCalled();
        $dummyPricePropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_FLOAT, true))
            ->withDescription('A dummy price.')
            ->withReadable(true)
            ->withWritable(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyPrice')->willReturn($dummyPricePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $actual = $apiPlatformParser->parse([
            'class' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'gerard'),
        ]);

        $this->assertEquals([
            'id' => [
                'dataType' => DataTypes::INTEGER,
                'required' => true,
                'description' => 'The id.',
                'readonly' => true,
            ],
            'name' => [
                'dataType' => DataTypes::STRING,
                'required' => true,
                'description' => 'The dummy name.',
                'readonly' => false,
            ],
            'dummyPrice' => [
                'dataType' => DataTypes::FLOAT,
                'required' => false,
                'description' => 'A dummy price.',
                'readonly' => false,
            ],
        ], $actual);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testParseDateTime()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy', 'dummy', null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::cetera())->willReturn(new PropertyNameCollection([
            'dummyDate',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $dummyDatePropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTime::class))
            ->withDescription('A dummy date.')
            ->withReadable(true)
            ->withWritable(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyDate')->willReturn($dummyDatePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $actual = $apiPlatformParser->parse([
            'class' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'),
        ]);

        $this->assertEquals([
            'dummyDate' => [
                'dataType' => DataTypes::DATETIME,
                'required' => false,
                'description' => 'A dummy date.',
                'readonly' => false,
                'format' => sprintf('{DateTime %s}', \DateTime::RFC3339),
            ],
        ], $actual);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testParseRelation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy', 'dummy', null, [], []))->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::cetera())->willReturn(new PropertyNameCollection([
            'relatedDummy',
            'relatedDummies',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class, Argument::cetera())->willReturn(new PropertyNameCollection([
            'id',
            'name',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relatedDummyPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_OBJECT, true, RelatedDummy::class))
            ->withDescription('A related dummy.')
            ->withReadable(true)
            ->withWritable(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->willReturn($relatedDummyPropertyMetadata)->shouldBeCalled();
        $relatedDummiesPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_OBJECT, false, Collection::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)))
            ->withDescription('Several dummies.')
            ->withReadable(true)
            ->withWritable(true)
            ->withReadableLink(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummies')->willReturn($relatedDummiesPropertyMetadata)->shouldBeCalled();
        $idPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_INT, false))
            ->withReadable(true)
            ->withWritable(false)
            ->withRequired(true);
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id')->willReturn($idPropertyMetadata)->shouldBeCalled();
        $namePropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_STRING, false))
            ->withDescription('A name.')
            ->withReadable(true)
            ->withWritable(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name')->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $actual = $apiPlatformParser->parse([
            'class' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'),
        ]);

        $this->assertEquals([
            'relatedDummy' => [
                'dataType' => 'IRI',
                'required' => false,
                'description' => 'A related dummy.',
                'readonly' => false,
                'actualType' => DataTypes::STRING,
            ],
            'relatedDummies' => [
                'dataType' => null,
                'required' => false,
                'description' => 'Several dummies.',
                'readonly' => false,
                'actualType' => DataTypes::COLLECTION,
                'subType' => RelatedDummy::class,
                'children' => [
                    'id' => [
                        'dataType' => DataTypes::INTEGER,
                        'required' => true,
                        'description' => null,
                        'readonly' => true,
                    ],
                    'name' => [
                        'dataType' => DataTypes::STRING,
                        'required' => false,
                        'description' => 'A name.',
                        'readonly' => false,
                    ],
                ],
            ],
        ], $actual);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testParseWithNameConverter()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy', 'dummy', null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::cetera())->willReturn(new PropertyNameCollection([
            'nameConverted',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $nameConvertedPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_STRING, true))
            ->withDescription('A converted name')
            ->withReadable(true)
            ->withWritable(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'nameConverted')->willReturn($nameConvertedPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('nameConverted', Dummy::class)->willReturn('name_converted')->shouldBeCalled();
        $nameConverter = $nameConverterProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, $nameConverter);

        $actual = $apiPlatformParser->parse([
            'class' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'),
        ]);

        $this->assertEquals([
            'name_converted' => [
                'dataType' => DataTypes::STRING,
                'required' => false,
                'description' => 'A converted name',
                'readonly' => false,
            ],
        ], $actual);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\NelmioApiDoc\Parser\ApiPlatformParser class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testParseRecursive()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy', 'dummy', null, [], []))->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::cetera())->willReturn(new PropertyNameCollection([
            'relatedDummy',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class, Argument::cetera())->willReturn(new PropertyNameCollection([
            'dummy',
        ]))->shouldBeCalled();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relatedDummyMetadatata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_OBJECT, true, RelatedDummy::class))
            ->withDescription('A related Dummy.')
            ->withReadable(true)
            ->withReadableLink(true)
            ->withWritableLink(true)
            ->withWritable(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->willReturn($relatedDummyMetadatata)->shouldBeCalled();
        $dummyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_OBJECT, true, Dummy::class))
            ->withDescription('A Dummy.')
            ->withReadable(true)
            ->withWritable(true)
            ->withReadableLink(true)
            ->withWritableLink(true)
            ->withRequired(false);
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'dummy')->willReturn($dummyMetadata)->shouldBeCalled();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $apiPlatformParser = new ApiPlatformParser($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory);

        $actual = $apiPlatformParser->parse([
            'class' => sprintf('%s:%s:%s', ApiPlatformParser::OUT_PREFIX, Dummy::class, 'get'),
        ]);

        $this->assertEquals([
            'relatedDummy' => [
                'dataType' => null,
                'required' => false,
                'description' => 'A related Dummy.',
                'readonly' => false,
                'actualType' => 'model',
                'subType' => RelatedDummy::class,
                'children' => [
                    'dummy' => [
                        'dataType' => null,
                        'required' => false,
                        'description' => 'A Dummy.',
                        'readonly' => false,
                        'actualType' => 'model',
                        'subType' => Dummy::class,
                        'children' => [],
                    ],
                ],
            ],
        ], $actual);
    }
}
