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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Extractor\ExtractorInterface;
use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ShortNameResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyResourceInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * Tests extractor resource metadata factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ExtractorResourceMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testXmlCreateResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    public function testXmlDoesNotExistMetadataFactory()
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('Resource "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" not found.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';
        $factory = new ExtractorResourceNameCollectionFactory(new XmlExtractor([$configPath]));
        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));

        foreach ($factory->create() as $resourceName) {
            $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testXmlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.xml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @expectedDeprecation Configuring "%s" tags without using a parent "%ss" tag is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     * @group legacy
     * @dataProvider legacyOperationsResourceMetadataProvider
     */
    public function testLegacyOperationsResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/legacyoperations.xml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider noCollectionOperationsResourceMetadataProvider
     */
    public function testXmlNoCollectionOperationsResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/nocollectionoperations.xml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider noItemOperationsResourceMetadataProvider
     */
    public function testXmlNoItemOperationsResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/noitemoperations.xml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    public function testInvalidXmlResourceMetadataFactory()
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.xml';
        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));

        $resourceMetadataFactory->create(FileConfigDummy::class);
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testXmlParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.xml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn(new ResourceMetadata(null, 'test'))->shouldBeCalled();

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);
        $expectedResourceMetadata = $expectedResourceMetadata->withDescription('test');

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testXmlExistingParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn($expectedResourceMetadata)->shouldBeCalled();

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlCreateResourceMetadata(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    public function testYamlDoesNotExistMetadataFactory()
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('Resource "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" not found.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';
        $factory = new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath]));
        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));

        foreach ($factory->create() as $resourceName) {
            $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testYamlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.yml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlSingleResourceMetadata(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/single_resource.yml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testYamlParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.yml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn(new ResourceMetadata(null, 'test'))->shouldBeCalled();

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);
        $expectedResourceMetadata = $expectedResourceMetadata->withDescription('test');

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlExistingParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn($expectedResourceMetadata)->shouldBeCalled();

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    public function testCreateWithMalformedYaml()
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateWithBadDeclaration()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"ApiPlatform\\\\Core\\\\Tests\\\\Fixtures\\\\TestBundle\\\\Entity\\\\Dummy" setting is expected to be null or an array, string given in ".+\\/Fixtures\\/FileConfigurations\\/bad_declaration\\.yml"\\./');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/bad_declaration.yml';

        (new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateShortNameResourceMetadataForClassWithoutNamespace()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourceswithoutnamespace.yml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
        $shortNameResourceMetadataFactory = new ShortNameResourceMetadataFactory($resourceMetadataFactory);

        $resourceMetadata = $shortNameResourceMetadataFactory->create(\DateTime::class);
        $this->assertSame(\DateTime::class, $resourceMetadata->getShortName());
    }

    public function testItSupportsInterfaceAsAResource()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/interface_resource.yml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
        $shortNameResourceMetadataFactory = new ShortNameResourceMetadataFactory($resourceMetadataFactory);

        $resourceMetadata = $shortNameResourceMetadataFactory->create(DummyResourceInterface::class);
        $this->assertSame('DummyResourceInterface', $resourceMetadata->getShortName());
    }

    public function testItFallbacksToDefaultConfiguration()
    {
        $defaults = [
            'shortName' => 'Default shortname should not be ignored',
            'description' => 'CHANGEME!',
            'collection_operations' => ['get'],
            'item_operations' => ['get', 'put'],
            'attributes' => [
                'pagination_items_per_page' => 4,
                'pagination_maximum_items_per_page' => 6,
                'stateless' => true,
            ],
        ];
        $resourceConfiguration = [
            Dummy::class => [
                'shortName' => null,
                'description' => null,
                'subresourceOperations' => null,
                'itemOperations' => ['get', 'delete'],
                'attributes' => [
                    'pagination_items_per_page' => null,
                    'pagination_maximum_items_per_page' => 10,
                    'stateless' => false,
                ],
            ],
        ];

        $extractor = new class($resourceConfiguration) implements ExtractorInterface {
            private $resources;

            public function __construct(array $resources)
            {
                $this->resources = $resources;
            }

            public function getResources(): array
            {
                return $this->resources;
            }
        };
        $factory = new ExtractorResourceMetadataFactory($extractor, null, $defaults);
        $metadata = $factory->create(Dummy::class);

        $this->assertNull($metadata->getShortName());
        $this->assertEquals('CHANGEME!', $metadata->getDescription());
        $this->assertEquals(['get'], $metadata->getCollectionOperations());
        $this->assertEquals(['get', 'delete'], $metadata->getItemOperations());
        $this->assertEquals(4, $metadata->getAttribute('pagination_items_per_page'));
        $this->assertEquals(10, $metadata->getAttribute('pagination_maximum_items_per_page'));
        $this->assertFalse($metadata->getAttribute('stateless'));
    }
}
