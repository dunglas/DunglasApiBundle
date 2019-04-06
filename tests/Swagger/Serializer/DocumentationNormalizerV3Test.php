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

namespace ApiPlatform\Core\Tests\Swagger\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouterOperationPathResolver;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactory;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Core\PathResolver\CustomOperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolver;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Tests\Fixtures\DummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
class DocumentationNormalizerV3Test extends TestCase
{
    public function testNormalize()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name', 'description']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], ['pagination_client_items_per_page' => true]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false, null, null, null, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'description')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is an initializable but not writable property.', true, false, true, true, false, false, null, null, [], null, true));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'custom2')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => [
                                    'type' => 'integer',
                                ],
                                'description' => 'The collection page number',
                            ],
                            [
                                'name' => 'itemsPerPage',
                                'in' => 'query',
                                'required' => false,
                                'schema' => [
                                    'type' => 'integer',
                                ],
                                'description' => 'The number of items per page',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The new Dummy resource',
                        ],
                        'summary' => 'Creates a Dummy resource.',
                        'responses' => [
                            '201' => [
                                'description' => 'Dummy resource created',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                                'links' => [
                                    'GetDummyItem' => [
                                        'operationId' => 'getDummyItem',
                                        'parameters' => ['id' => '$response.body#/id'],
                                        'description' => 'The `id` value returned in the response can be used as the `id` parameter in `GET /dummies/{id}`.',
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The updated Dummy resource',
                        ],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource updated',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/foo' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'customDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The collection page number',
                            ],
                            [
                                'name' => 'itemsPerPage',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The number of items per page',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'custom2DummyCollection',
                        'summary' => 'Creates a Dummy resource.',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The new Dummy resource',
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Dummy resource created',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                                'links' => [
                                    'GetDummyItem' => [
                                        'operationId' => 'getDummyItem',
                                        'parameters' => ['id' => '$response.body#/id'],
                                        'description' => 'The `id` value returned in the response can be used as the `id` parameter in `GET /dummies/{id}`.',
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'id' => new \ArrayObject([
                                'type' => 'integer',
                                'description' => 'This is an id.',
                                'readOnly' => true,
                            ]),
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                            'description' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is an initializable but not writable property.',
                            ]),
                        ],
                    ]),
                ]),
            ],
            'servers' => [['url' => '/app_dev.php/']],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
    }

    public function testNormalizeWithNameConverter()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Dummy API', 'This is a dummy API', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name', 'nameConverted']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['name', 'nameConverted']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', null, ['get' => ['method' => 'GET']], [], []);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, null, null, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'nameConverted')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a converted name.', true, true, null, null, false));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name', Dummy::class, DocumentationNormalizer::FORMAT, [])->willReturn('name')->shouldBeCalled();
        $nameConverterProphecy->normalize('nameConverted', Dummy::class, DocumentationNormalizer::FORMAT, [])->willReturn('name_converted')->shouldBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            $nameConverterProphecy->reveal(),
            true,
            'oauth2',
            'application',
            '/oauth/v2/token',
            '/oauth/v2/auth',
            ['scope param'],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Dummy API',
                'description' => 'This is a dummy API',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                            'name_converted' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a converted name.',
                            ]),
                        ],
                    ]),
                ]),
                'securitySchemes' => [
                    'oauth' => [
                        'type' => 'oauth2',
                        'description' => 'OAuth client_credentials Grant',
                        'flow' => 'application',
                        'tokenUrl' => '/oauth/v2/token',
                        'authorizationUrl' => '/oauth/v2/auth',
                        'scopes' => ['scope param'],
                    ],
                ],
            ],
            'security' => [['oauth' => []]],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithApiKeysEnabled()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', null, ['get' => ['method' => 'GET']], [], []);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, null, null, false));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $apiKeysConfiguration = [
            'header' => [
                'type' => 'header',
                'name' => 'Authorization',
            ],
            'query' => [
                'type' => 'query',
                'name' => 'key',
            ],
        ];

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            $apiKeysConfiguration,
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'servers' => [['url' => '/app_dev.php/']],
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                        ],
                    ]),
                ]),
                'securitySchemes' => [
                    'header' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'description' => 'Value for the Authorization header',
                        'name' => 'Authorization',
                    ],
                    'query' => [
                        'type' => 'apiKey',
                        'in' => 'query',
                        'description' => 'Value for the key query parameter',
                        'name' => 'key',
                    ],
                ],
            ],
            'security' => [
                ['header' => []],
                ['query' => []],
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
    }

    public function testNormalizeWithOnlyNormalizationGroups()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);
        $groups = ['dummy', 'foo', 'bar'];

        $ref = 'Dummy-'.implode('_', $groups);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => $groups])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'normalization_context' => [AbstractNormalizer::GROUPS => $groups]],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => [
                            'Dummy',
                        ],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The collection page number',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'summary' => 'Creates a Dummy resource.',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The new Dummy resource',
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Dummy resource created',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The updated Dummy resource',
                        ],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource updated',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/'.$ref],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                        ],
                    ]),
                    $ref => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'gerard' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a gerard.',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithOpenApiDefinitionName()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['id']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => [
                    'method' => 'GET',
                    'normalization_context' => [
                        DocumentationNormalizer::SWAGGER_DEFINITION_NAME => 'Read',
                    ],
                ],
            ]
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy-Read'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy-Read' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'id' => new \ArrayObject([
                                'type' => 'integer',
                                'description' => 'This is an id.',
                                'readOnly' => true,
                            ]),
                        ],
                    ]),
                ]),
            ],
            'servers' => [['url' => '/app_dev.php/']],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
    }

    public function testNormalizeWithOnlyDenormalizationGroups()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'denormalization_context' => [AbstractNormalizer::GROUPS => 'dummy']],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => [
                            'Dummy',
                        ],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The collection page number',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The new Dummy resource',
                        ],
                        'summary' => 'Creates a Dummy resource.',
                        'responses' => [
                            '201' => [
                                'description' => 'Dummy resource created',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [[
                            'name' => 'id',
                            'in' => 'path',
                            'schema' => ['type' => 'string'],
                            'required' => true,
                        ]],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy-dummy'],
                                ],
                            ],
                            'description' => 'The updated Dummy resource',
                        ],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource updated',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                        ],
                    ]),
                    'Dummy-dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'gerard' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a gerard.',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithNormalizationAndDenormalizationGroups()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => 'dummy'])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['gerard']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => [
                    'method' => 'PUT',
                    'normalization_context' => [AbstractNormalizer::GROUPS => 'dummy'], 'denormalization_context' => [AbstractNormalizer::GROUPS => 'dummy'],
                ],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'gerard')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a gerard.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => [
                            'Dummy',
                        ],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The collection page number',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The new Dummy resource',
                        ],
                        'summary' => 'Creates a Dummy resource.',
                        'responses' => [
                            '201' => [
                                'description' => 'Dummy resource created',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [[
                            'name' => 'id',
                            'in' => 'path',
                            'schema' => ['type' => 'string'],
                            'required' => true,
                        ]],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy-dummy'],
                                ],
                            ],
                            'description' => 'The updated Dummy resource',
                        ],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource updated',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy-dummy'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                        ],
                    ]),
                    'Dummy-dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'gerard' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a gerard.',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testFilters()
    {
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filters = [
            'f1' => new DummyFilter(['name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => true,
                'strategy' => 'exact',
                'openapi' => ['x-foo' => 'bar'],
            ]]),
            'f2' => new DummyFilter(['ha' => [
                'property' => 'foo',
                'type' => 'int',
                'required' => false,
                'strategy' => 'partial',
            ]]),
            'f3' => new DummyFilter(['toto' => [
                'property' => 'name',
                'type' => 'array',
                'is_collection' => true,
                'required' => true,
                'strategy' => 'exact',
            ]]),
        ];

        foreach ($filters as $filterId => $filter) {
            $filterLocatorProphecy->has($filterId)->willReturn(true)->shouldBeCalled();
            $filterLocatorProphecy->get($filterId)->willReturn($filter)->shouldBeCalled();
        }

        $filterLocatorProphecy->has('f4')->willReturn(false)->shouldBeCalled();

        $this->normalizeWithFilters($filterLocatorProphecy->reveal());
    }

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testFiltersWithDeprecatedFilterCollection()
    {
        $this->normalizeWithFilters(new FilterCollection([
            'f1' => new DummyFilter(['name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => true,
                'strategy' => 'exact',
                'openapi' => ['x-foo' => 'bar'],
            ]]),
            'f2' => new DummyFilter(['ha' => [
                'property' => 'foo',
                'type' => 'int',
                'required' => false,
                'strategy' => 'partial',
            ]]),
            'f3' => new DummyFilter(['toto' => [
                'property' => 'name',
                'type' => 'array',
                'is_collection' => true,
                'required' => true,
                'strategy' => 'exact',
            ]]),
        ]));
    }

    public function testConstructWithInvalidFilterLocator()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$filterLocator" argument is expected to be an implementation of the "Psr\\Container\\ContainerInterface" interface or null.');

        new DocumentationNormalizer(
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(OperationMethodResolverInterface::class)->reveal(),
            $this->prophesize(OperationPathResolverInterface::class)->reveal(),
            null,
            new \ArrayObject(),
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );
    }

    public function testSupports()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $this->assertTrue($normalizer->supportsNormalization($documentation, 'json'));
        $this->assertFalse($normalizer->supportsNormalization($documentation));
        $this->assertFalse($normalizer->supportsNormalization(new Dummy(), 'json'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNoOperations()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), '', '', '0.0.0', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldNotBeCalled();

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            [],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldNotBeCalled();

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => '',
                'version' => '0.0.0',
            ],
            'paths' => new \ArrayObject([]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testWithCustomMethod()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), '', '', '0.0.0', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            ['get' => ['method' => 'FOO']],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('FOO');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => '',
                'version' => '0.0.0',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'foo' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                    ]),
                ],
            ]),
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithNestedNormalizationGroups()
    {
        $title = 'Test API';
        $description = 'This is a test API.';
        $formats = ['jsonld' => ['application/ld+json']];
        $version = '1.2.3';
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), $title, $description, $version, $formats);
        $groups = ['dummy', 'foo', 'bar'];
        $ref = 'Dummy-'.implode('_', $groups);
        $relatedDummyRef = 'RelatedDummy-'.implode('_', $groups);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, ['serializer_groups' => $groups])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['name', 'relatedDummy']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));
        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class, ['serializer_groups' => $groups])->shouldBeCalled(1)->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'normalization_context' => [AbstractNormalizer::GROUPS => $groups]],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );

        $relatedDummyMetadata = new ResourceMetadata(
            'RelatedDummy',
            'This is a related dummy.',
            'http://schema.example.com/RelatedDummy',
            [
                'get' => ['method' => 'GET'],
            ],
            [],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedDummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, true, RelatedDummy::class), 'This is a related dummy \o/.', true, true, true, true, false, false, null, null, []));
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => [
                            'Dummy',
                        ],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The collection page number',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The new Dummy resource',
                        ],
                        'summary' => 'Creates a Dummy resource.',
                        'responses' => [
                            '201' => [
                                'description' => 'Dummy resource created',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [[
                            'name' => 'id',
                            'in' => 'path',
                            'schema' => ['type' => 'string'],
                            'required' => true,
                        ]],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'requestBody' => [
                            'content' => [
                                'application/ld+json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The updated Dummy resource',
                        ],
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource updated',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/'.$ref],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Invalid input'],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                        ],
                    ]),
                    $ref => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                            'relatedDummy' => new \ArrayObject([
                                'description' => 'This is a related dummy \o/.',
                                '$ref' => '#/components/schemas/'.$relatedDummyRef,
                            ]),
                        ],
                    ]),
                    $relatedDummyRef => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a related dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/RelatedDummy'],
                        'properties' => [
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    private function normalizeWithFilters($filterLocator)
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), '', '', '0.0.0', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['name']));

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            ['get' => ['method' => 'GET', 'filters' => ['f1', 'f2', 'f3', 'f4']]],
            []
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            $filterLocator,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => '',
                'version' => '0.0.0',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'parameters' => [
                            [
                                'x-foo' => 'bar',
                                'name' => 'name',
                                'in' => 'query',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                            ],
                            [
                                'name' => 'ha',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                            ],
                            [
                                'name' => 'toto',
                                'in' => 'query',
                                'required' => true,
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'style' => 'deepObject',
                                'explode' => true,
                            ],
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The collection page number',
                            ],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'properties' => [
                            'name' => new \ArrayObject([
                                'description' => 'This is a name.',
                                'type' => 'string',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithSubResource()
    {
        $documentation = new Documentation(new ResourceNameCollection([Question::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Question::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['answer']));
        $propertyNameCollectionFactoryProphecy->create(Answer::class, Argument::any())->shouldBeCalled()->willReturn(new PropertyNameCollection(['content']));

        $questionMetadata = new ResourceMetadata('Question', 'This is a question.', 'http://schema.example.com/Question', ['get' => ['method' => 'GET']]);
        $answerMetadata = new ResourceMetadata('Answer', 'This is an answer.', 'http://schema.example.com/Answer', ['get' => ['method' => 'GET']], []);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Question::class)->shouldBeCalled()->willReturn($questionMetadata);
        $resourceMetadataFactoryProphecy->create(Answer::class)->shouldBeCalled()->willReturn($answerMetadata);

        $subresourceMetadata = new SubresourceMetadata(Answer::class, false);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Question::class, 'answer')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, Question::class, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, Answer::class)), 'This is a name.', true, true, true, true, false, false, null, null, [], $subresourceMetadata));

        $propertyMetadataFactoryProphecy->create(Answer::class, 'content')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, Question::class, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, Answer::class)), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Question::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(Answer::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Question::class, 'get')->shouldBeCalled()->willReturn('GET');

        $routeCollection = new RouteCollection();
        $routeCollection->add('api_questions_answer_item_get_subresource', new Route('/api/questions/{id}/answer.{_format}'));
        $routeCollection->add('api_questions_get_item', new Route('/api/questions/{id}.{_format}'));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->shouldBeCalled()->willReturn($routeCollection);

        $operationPathResolver = new RouterOperationPathResolver($routerProphecy->reveal(), new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator())));

        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();
        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $subresourceOperationFactory = new SubresourceOperationFactory($resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, new UnderscorePathSegmentNameGenerator());

        $formatProviderProphecy = $this->prophesize(OperationAwareFormatsProviderInterface::class);
        $formatProviderProphecy->getFormatsFromOperation(Question::class, 'get', OperationType::ITEM)->willReturn(['json' => ['application/json'], 'csv' => ['text/csv']]);
        $formatProviderProphecy->getFormatsFromOperation(Answer::class, 'get', OperationType::SUBRESOURCE)->willReturn(['xml' => ['text/xml']]);

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            $subresourceOperationFactory,
            true,
            'page',
            false,
            'itemsPerPage',
            $formatProviderProphecy->reveal(),
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/api/questions/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Question'],
                        'operationId' => 'getQuestionItem',
                        'summary' => 'Retrieves a Question resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Question resource response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Question'],
                                    ],
                                    'text/csv' => [
                                        'schema' => ['$ref' => '#/components/schemas/Question'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/api/questions/{id}/answer' => new \ArrayObject([
                    'get' => new \ArrayObject([
                        'tags' => ['Question', 'Answer'],
                        'operationId' => 'api_questions_answer_item_get_subresource',
                        'summary' => 'Retrieves a Answer resource.',
                        'responses' => [
                            '200' => [
                                'description' => 'Answer resource response',
                                'content' => [
                                    'text/xml' => [
                                        'schema' => ['$ref' => '#/components/schemas/Answer'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                    ]),
                ]),
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Question' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a question.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Question'],
                        'properties' => [
                            'answer' => new \ArrayObject([
                                'type' => 'array',
                                'description' => 'This is a name.',
                                'items' => ['$ref' => '#/components/schemas/Answer'],
                            ]),
                        ],
                    ]),
                    'Answer' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is an answer.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Answer'],
                        'properties' => [
                            'content' => new \ArrayObject([
                                'type' => 'array',
                                'description' => 'This is a name.',
                                'items' => ['$ref' => '#/components/schemas/Answer'],
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation));
    }

    public function testNormalizeWithPropertyOpenApiContext()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', ['get' => ['method' => 'GET']]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, ['openapi_context' => ['type' => 'string', 'enum' => ['one', 'two'], 'example' => 'one']]));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'servers' => [['url' => '/app_dev.php/']],
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'id' => new \ArrayObject([
                                'type' => 'integer',
                                'description' => 'This is an id.',
                                'readOnly' => true,
                            ]),
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                                'enum' => ['one', 'two'],
                                'example' => 'one',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
    }

    public function testNormalizeWithPaginationClientEnabled()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', [], ['get' => ['method' => 'GET', 'pagination_client_enabled' => true]]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, ['openapi_context' => ['type' => 'string', 'enum' => ['one', 'two'], 'example' => 'one']]));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');

        $operationPathResolver = new CustomOperationPathResolver(new OperationPathResolver(new UnderscorePathSegmentNameGenerator()));

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null,
            false,
            '',
            '',
            '',
            '',
            [],
            [],
            null,
            true,
            'page',
            false,
            'itemsPerPage',
            null,
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'servers' => [['url' => '/app_dev.php/']],
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                                'description' => 'The collection page number',
                            ],
                            [
                                'name' => 'pagination',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'boolean'],
                                'description' => 'Enable or disable pagination',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/ld+json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'id' => new \ArrayObject([
                                'type' => 'integer',
                                'description' => 'This is an id.',
                                'readOnly' => true,
                            ]),
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                                'enum' => ['one', 'two'],
                                'example' => 'one',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/app_dev.php/']));
    }

    public function testNormalizeWithCustomFormatsDefinedAtOperationLevel()
    {
        $documentation = new Documentation(new ResourceNameCollection([Dummy::class]), 'Test API', 'This is a test API.', '1.2.3', ['jsonld' => ['application/ld+json']]);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name']));
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new PropertyNameCollection(['id', 'name']));

        $dummyMetadata = new ResourceMetadata('Dummy', 'This is a dummy.', 'http://schema.example.com/Dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'This is an id.', true, false, null, null, null, true));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->shouldBeCalled()->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'This is a name.', true, true, true, true, false, false, null, null, []));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->shouldBeCalled()->willReturn('POST');

        $operationPathResolver = new OperationPathResolver(new UnderscorePathSegmentNameGenerator());

        $formatProviderProphecy = $this->prophesize(OperationAwareFormatsProviderInterface::class);
        $formatProviderProphecy->getFormatsFromOperation(Dummy::class, 'get', OperationType::COLLECTION)->willReturn(['xml' => ['application/xml', 'text/xml']]);
        $formatProviderProphecy->getFormatsFromOperation(Dummy::class, 'post', OperationType::COLLECTION)->willReturn(['xml' => ['text/xml'], 'csv' => ['text/csv']]);
        $formatProviderProphecy->getFormatsFromOperation(Dummy::class, 'get', OperationType::ITEM)->willReturn(['jsonapi' => ['application/vnd.api+json']]);
        $formatProviderProphecy->getFormatsFromOperation(Dummy::class, 'put', OperationType::ITEM)->willReturn(['json' => ['application/json'], 'csv' => ['text/csv']]);

        $normalizer = new DocumentationNormalizer(
            $resourceMetadataFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal(),
            $operationPathResolver,
            null,
            null,
            null, false,
            '',
            '',
            '',
            '',
            [],
            [],

            null,
            false,
            'page',
            false,
            'itemsPerPage',
            $formatProviderProphecy->reveal(),
            false,
            'pagination',
            ['spec_version' => 3]
        );

        $expected = [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'Test API',
                'description' => 'This is a test API.',
                'version' => '1.2.3',
            ],
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyCollection',
                        'summary' => 'Retrieves the collection of Dummy resources.',
                        'parameters' => [],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy collection response',
                                'content' => [
                                    'application/xml' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                    'text/xml' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Dummy'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    'post' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'postDummyCollection',
                        'summary' => 'Creates a Dummy resource.',
                        'requestBody' => [
                            'content' => [
                                'text/xml' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                                'text/csv' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The new Dummy resource',
                        ],
                        'responses' => [
                            201 => [
                                'description' => 'Dummy resource created',
                                'content' => [
                                    'text/xml' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                    'text/csv' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                                'links' => [
                                    'GetDummyItem' => [
                                        'operationId' => 'getDummyItem',
                                        'parameters' => ['id' => '$response.body#/id'],
                                        'description' => 'The `id` value returned in the response can be used as the `id` parameter in `GET /dummies/{id}`.',
                                    ],
                                ],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'getDummyItem',
                        'summary' => 'Retrieves a Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource response',
                                'content' => [
                                    'application/vnd.api+json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                    'put' => new \ArrayObject([
                        'tags' => ['Dummy'],
                        'operationId' => 'putDummyItem',
                        'summary' => 'Replaces the Dummy resource.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'schema' => ['type' => 'string'],
                                'required' => true,
                            ],
                        ],
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                                'text/csv' => [
                                    'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                ],
                            ],
                            'description' => 'The updated Dummy resource',
                        ],
                        'responses' => [
                            200 => [
                                'description' => 'Dummy resource updated',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                    'text/csv' => [
                                        'schema' => ['$ref' => '#/components/schemas/Dummy'],
                                    ],
                                ],
                            ],
                            400 => ['description' => 'Invalid input'],
                            404 => ['description' => 'Resource not found'],
                        ],
                    ]),
                ],
            ]),
            'components' => [
                'schemas' => new \ArrayObject([
                    'Dummy' => new \ArrayObject([
                        'type' => 'object',
                        'description' => 'This is a dummy.',
                        'externalDocs' => ['url' => 'http://schema.example.com/Dummy'],
                        'properties' => [
                            'id' => new \ArrayObject([
                                'type' => 'integer',
                                'description' => 'This is an id.',
                                'readOnly' => true,
                            ]),
                            'name' => new \ArrayObject([
                                'type' => 'string',
                                'description' => 'This is a name.',
                            ]),
                        ],
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['base_url' => '/']));
    }
}
