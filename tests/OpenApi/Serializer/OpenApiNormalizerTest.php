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

namespace ApiPlatform\Core\Tests\OpenApi\Serializer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\PaginationOptions;
use ApiPlatform\Core\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\TypeFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\OpenApi\Factory\LegacyOpenApiFactory;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Options;
use ApiPlatform\Core\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolver;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class OpenApiNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private const OPERATION_FORMATS = [
        'input_formats' => ['jsonld' => ['application/ld+json']],
        'output_formats' => ['jsonld' => ['application/ld+json']],
    ];

    /**
     * @groupe legacy
     */
    public function testLegacyFactoryNormalize()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->shouldBeCalled()->willReturn(new ResourceNameCollection([Dummy::class, 'Zorro']));
        $defaultContext = ['base_url' => '/app_dev.php/'];
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'dummyDate']));
        $propertyNameCollectionFactoryProphecy->create('Zorro', Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'] + self::OPERATION_FORMATS,
                'put' => ['method' => 'PUT'] + self::OPERATION_FORMATS,
                'delete' => ['method' => 'DELETE'] + self::OPERATION_FORMATS,
            ],
            [
                'get' => ['method' => 'GET'] + self::OPERATION_FORMATS,
                'post' => ['method' => 'POST', 'openapi_context' => ['security' => [], 'servers' => ['url' => '/test']]] + self::OPERATION_FORMATS,
            ],
            []
        );

        $zorroMetadata = new ResourceMetadata(
            'Zorro',
            'This is zorro.',
            'http://schema.example.com/Zorro',
            [
                'get' => ['method' => 'GET'] + self::OPERATION_FORMATS,
            ],
            [
                'get' => ['method' => 'GET'] + self::OPERATION_FORMATS,
            ],
            []
        );

        $subresourceOperationFactoryProphecy = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $subresourceOperationFactoryProphecy->create(Argument::any(), Argument::any(), Argument::any())->willReturn([]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);
        $resourceMetadataFactoryProphecy->create('Zorro')->shouldBeCalled()->willReturn($zorroMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false, null, null, null, true, null, null, null, null, null, null, null));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, [], null, null, null, null, ['minLength' => 3, 'maxLength' => 20, 'pattern' => '^dummyPattern$']));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'description', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is an initializable but not writable property.', true, false, true, true, false, false, null, null, [], null, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyDate', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTime::class), 'This is a \DateTimeInterface object.', true, true, true, true, false, false, null, null, []));

        $propertyMetadataFactoryProphecy->create('Zorro', 'id', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false, null, null, null, true));

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $typeFactory = new TypeFactory();
        $schemaFactory = new SchemaFactory($typeFactory, $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, new CamelCaseToSnakeCaseNameConverter());
        $typeFactory->setSchemaFactory($schemaFactory);

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $factory = new LegacyOpenApiFactory(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $schemaFactory,
            $typeFactory,
            $operationPathResolver,
            $filterLocatorProphecy->reveal(),
            $subresourceOperationFactoryProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            [],
            new Options('Test API', 'This is a test API.', '1.2.3', true, 'oauth2', 'authorizationCode', '/oauth/v2/token', '/oauth/v2/auth', '/oauth/v2/refresh', ['scope param'], [
                'header' => [
                    'type' => 'header',
                    'name' => 'Authorization',
                ],
                'query' => [
                    'type' => 'query',
                    'name' => 'key',
                ],
            ]),
            new PaginationOptions(true, 'page', true, 'itemsPerPage', true, 'pagination')
        );

        $openApi = $factory(['base_url' => '/app_dev.php/']);

        $pathItem = $openApi->getPaths()->getPath('/dummies/{id}');
        $operation = $pathItem->getGet();

        $openApi->getPaths()->addPath('/dummies/{id}', $pathItem->withGet(
            $operation->withParameters(array_merge(
                $operation->getParameters(),
                [new Model\Parameter('fields', 'query', 'Fields to remove of the output')]
            ))
        ));

        $openApi = $openApi->withInfo((new Model\Info('New Title', 'v2', 'Description of my custom API'))->withExtensionProperty('info-key', 'Info value'));
        $openApi = $openApi->withExtensionProperty('key', 'Custom x-key value');
        $openApi = $openApi->withExtensionProperty('x-value', 'Custom x-value value');

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $normalizers[0]->setSerializer($serializer);

        $normalizer = new OpenApiNormalizer($normalizers[0]);

        $openApiAsArray = $normalizer->normalize($openApi);

        // Just testing normalization specifics
        $this->assertEquals($openApiAsArray['x-key'], 'Custom x-key value');
        $this->assertEquals($openApiAsArray['x-value'], 'Custom x-value value');
        $this->assertEquals($openApiAsArray['info']['x-info-key'], 'Info value');
        $this->assertArrayNotHasKey('extensionProperties', $openApiAsArray);
        // this key is null, should not be in the output
        $this->assertArrayNotHasKey('termsOfService', $openApiAsArray['info']);
        $this->assertArrayNotHasKey('paths', $openApiAsArray['paths']);
        $this->assertArrayHasKey('/dummies/{id}', $openApiAsArray['paths']);
        $this->assertArrayNotHasKey('servers', $openApiAsArray['paths']['/dummies/{id}']['get']);
        $this->assertArrayNotHasKey('security', $openApiAsArray['paths']['/dummies/{id}']['get']);

        // Security can be disabled per-operation using an empty array
        $this->assertEquals([], $openApiAsArray['paths']['/dummies']['post']['security']);
        $this->assertEquals(['url' => '/test'], $openApiAsArray['paths']['/dummies']['post']['servers']);

        // Make sure things are sorted
        $this->assertEquals(array_keys($openApiAsArray['paths']), ['/dummies', '/dummies/{id}', '/zorros', '/zorros/{id}']);
        // Test name converter doesn't rename this property
        $this->assertArrayHasKey('requestBody', $openApiAsArray['paths']['/dummies']['post']);
    }

    public function testNormalizeWithSchemas()
    {
        $openApi = new OpenApi(new Model\Info('My API', '1.0.0', 'An amazing API'), [new Model\Server('https://example.com')], new Model\Paths(), new Model\Components(new \ArrayObject(['z' => [], 'b' => []])));
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $normalizers[0]->setSerializer($serializer);

        $normalizer = new OpenApiNormalizer($normalizers[0]);

        $array = $normalizer->normalize($openApi);

        $this->assertEquals(array_keys($array['components']['schemas']), ['b', 'z']);
    }

    public function testNormalizeWithEmptySchemas()
    {
        $openApi = new OpenApi(new Model\Info('My API', '1.0.0', 'An amazing API'), [new Model\Server('https://example.com')], new Model\Paths(), new Model\Components(new \ArrayObject()));
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $normalizers[0]->setSerializer($serializer);

        $normalizer = new OpenApiNormalizer($normalizers[0]);

        $array = $normalizer->normalize($openApi);
        $this->assertCount(0, $array['components']['schemas']);
    }

    /**
     * @requires php 8.0
     */
    public function testNormalize()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->shouldBeCalled()->willReturn(new ResourceNameCollection([Dummy::class, 'Zorro']));
        $defaultContext = ['base_url' => '/app_dev.php/'];
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description', 'dummyDate']));
        $propertyNameCollectionFactoryProphecy->create('Zorro', Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));

        $dummyMetadata = new ResourceCollection([
            new Resource(
                shortName: 'Dummy',
                description: 'This is a dummy.',
                types: ['http://schema.example.com/Dummy'],
                operations: [
                    'get' => new Get(uriTemplate: '/dummies/{id}', inputFormats: self::OPERATION_FORMATS['input_formats'], outputFormats: self::OPERATION_FORMATS['output_formats']),
                    'put' => new Put(inputFormats: self::OPERATION_FORMATS['input_formats'], outputFormats: self::OPERATION_FORMATS['output_formats']),
                    'delete' => new Delete(inputFormats: self::OPERATION_FORMATS['input_formats'], outputFormats: self::OPERATION_FORMATS['output_formats']),
                    'get_collection' => new GetCollection(uriTemplate: '/dummies', inputFormats: self::OPERATION_FORMATS['input_formats'], outputFormats: self::OPERATION_FORMATS['output_formats']),
                    'post' => new Post(uriTemplate: '/dummies', openapiContext: ['security' => [], 'servers' => ['url' => '/test']], inputFormats: self::OPERATION_FORMATS['input_formats'], outputFormats: self::OPERATION_FORMATS['output_formats']),
                ]
            ),
        ]
        );

        $zorroMetadata = new ResourceCollection(
            [
                new Resource(
                    shortName: 'Zorro',
                    description: 'This is zorro.',
                    types: ['http://schema.example.com/Zorro'],
                    operations: [
                        'get' => new Get(uriTemplate: '/zorros/{id}', shortName: 'Zorro', inputFormats: self::OPERATION_FORMATS['input_formats'], outputFormats: self::OPERATION_FORMATS['output_formats']),
                        'get_collection' => new GetCollection(uriTemplate: '/zorros', shortName: 'Zorro', inputFormats: self::OPERATION_FORMATS['input_formats'], outputFormats: self::OPERATION_FORMATS['output_formats']),
                    ]
                ),
            ]
        );

        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);
        $resourceCollectionMetadataFactoryProphecy->create('Zorro')->shouldBeCalled()->willReturn($zorroMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false, null, null, null, true, null, null, null, null, null, null, null));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, [], null, null, null, null, ['minLength' => 3, 'maxLength' => 20, 'pattern' => '^dummyPattern$']));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'description', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is an initializable but not writable property.', true, false, true, true, false, false, null, null, [], null, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyDate', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTime::class), 'This is a \DateTimeInterface object.', true, true, true, true, false, false, null, null, []));

        $propertyMetadataFactoryProphecy->create('Zorro', 'id', Argument::any())->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false, null, null, null, true));

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $resourceMetadataFactory = $resourceCollectionMetadataFactoryProphecy->reveal();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $typeFactory = new TypeFactory();
        $schemaFactory = new SchemaFactory($typeFactory, $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, new CamelCaseToSnakeCaseNameConverter());
        $typeFactory->setSchemaFactory($schemaFactory);

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(Argument::type('string'))->willReturn(['id']);

        $factory = new OpenApiFactory(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $schemaFactory,
            $typeFactory,
            $operationPathResolver,
            $filterLocatorProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            [],
            new Options('Test API', 'This is a test API.', '1.2.3', true, 'oauth2', 'authorizationCode', '/oauth/v2/token', '/oauth/v2/auth', '/oauth/v2/refresh', ['scope param'], [
                'header' => [
                    'type' => 'header',
                    'name' => 'Authorization',
                ],
                'query' => [
                    'type' => 'query',
                    'name' => 'key',
                ],
            ]),
            new PaginationOptions(true, 'page', true, 'itemsPerPage', true, 'pagination')
        );

        $openApi = $factory(['base_url' => '/app_dev.php/']);

        $pathItem = $openApi->getPaths()->getPath('/dummies/{id}');
        $operation = $pathItem->getGet();

        $openApi->getPaths()->addPath('/dummies/{id}', $pathItem->withGet(
            $operation->withParameters(array_merge(
                $operation->getParameters(),
                [new Model\Parameter('fields', 'query', 'Fields to remove of the output')]
            ))
        ));

        $openApi = $openApi->withInfo((new Model\Info('New Title', 'v2', 'Description of my custom API'))->withExtensionProperty('info-key', 'Info value'));
        $openApi = $openApi->withExtensionProperty('key', 'Custom x-key value');
        $openApi = $openApi->withExtensionProperty('x-value', 'Custom x-value value');

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $normalizers[0]->setSerializer($serializer);

        $normalizer = new OpenApiNormalizer($normalizers[0]);

        $openApiAsArray = $normalizer->normalize($openApi);

        // Just testing normalization specifics
        $this->assertEquals($openApiAsArray['x-key'], 'Custom x-key value');
        $this->assertEquals($openApiAsArray['x-value'], 'Custom x-value value');
        $this->assertEquals($openApiAsArray['info']['x-info-key'], 'Info value');
        $this->assertArrayNotHasKey('extensionProperties', $openApiAsArray);
        // this key is null, should not be in the output
        $this->assertArrayNotHasKey('termsOfService', $openApiAsArray['info']);
        $this->assertArrayNotHasKey('paths', $openApiAsArray['paths']);
        $this->assertArrayHasKey('/dummies/{id}', $openApiAsArray['paths']);
        $this->assertArrayNotHasKey('servers', $openApiAsArray['paths']['/dummies/{id}']['get']);
        $this->assertArrayNotHasKey('security', $openApiAsArray['paths']['/dummies/{id}']['get']);

        // Security can be disabled per-operation using an empty array
        $this->assertEquals([], $openApiAsArray['paths']['/dummies']['post']['security']);
        $this->assertEquals(['url' => '/test'], $openApiAsArray['paths']['/dummies']['post']['servers']);

        // Make sure things are sorted
        $this->assertEquals(array_keys($openApiAsArray['paths']), ['/dummies', '/dummies/{id}', '/zorros', '/zorros/{id}']);
        // Test name converter doesn't rename this property
        $this->assertArrayHasKey('requestBody', $openApiAsArray['paths']['/dummies']['post']);
    }
}
