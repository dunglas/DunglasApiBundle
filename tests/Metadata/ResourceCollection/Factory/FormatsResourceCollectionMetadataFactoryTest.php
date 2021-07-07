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

namespace ApiPlatform\Core\Tests\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\FormatsResourceCollectionMetadataFactory;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Resource;
use PHPUnit\Framework\TestCase;

class FormatsResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Resource $previous, Resource $expected, array $formats = [], array $patchFormats = []): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceCollection([$previous]));

        $actual = (new FormatsResourceCollectionMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $formats, $patchFormats))->create('Foo')[0];
        $this->assertEquals($expected, $actual);
    }

    public function createProvider(): iterable
    {
        yield [
            new Resource(
                formats: 'json',
                operations: [new Get()]
            ),
            new Resource(
                formats: 'json',
                operations: [new Get(inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
            ['json' => ['application/json']],
        ];

        yield [
            new Resource(
                formats: ['json' => ['application/json']],
                operations: [new Get()]
            ),
            new Resource(
                formats: ['json' => ['application/json']],
                operations: [new Get(inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
        ];

        yield [
            new Resource(
                inputFormats: ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']],
                outputFormats: ['csv' => ['text/csv']],
                operations: [new Get()]
            ),
            new Resource(
                inputFormats: ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']],
                outputFormats: ['csv' => ['text/csv']],
                operations: [new Get(
                    inputFormats: ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']],
                    outputFormats: ['csv' => ['text/csv']],
                )]
            ),
        ];

        yield [
            new Resource(
                operations: [
                    new Patch(),
                ]
            ),
            new Resource(
                operations: [
                    new Patch(inputFormats: ['json' => ['application/merge-patch+json']], outputFormats: []),
                ]
            ),
            [],
            ['json' => ['application/merge-patch+json']],
        ];

        yield [
            new Resource(
                operations: [new Get(formats: 'json')]
            ),
            new Resource(
                operations: [new Get(formats: ['json' => ['application/json']], inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
            ['json' => ['application/json']],
        ];

        yield [
            new Resource(
                operations: [new Get(inputFormats: 'json', outputFormats: 'json')]
            ),
            new Resource(
                operations: [new Get(inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
            ['json' => ['application/json']],
        ];
    }

    public function testInvalidFormatType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'formats\' attributes value must be a string when trying to include an already configured format, object given.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceCollection([new Resource(formats: [new \stdClass()])]));

        (new FormatsResourceCollectionMetadataFactory($resourceMetadataFactoryProphecy->reveal(), [], []))->create('Foo');
    }

    public function testNotConfiguredFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You either need to add the format \'xml\' to your project configuration or declare a mime type for it in your annotation.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceCollectionMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceCollection([new Resource(formats: ['xml'])]));

        (new FormatsResourceCollectionMetadataFactory($resourceMetadataFactoryProphecy->reveal(), [], []))->create('Foo');
    }
}
