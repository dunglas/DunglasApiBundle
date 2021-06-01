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
use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * Tests extractor resource name collection factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ExtractorResourceNameCollectionFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testXmlResourceName()
    {
        $this->expectDeprecation('Using a legacy ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory is deprecated since 2.7 and will not be possible in 3.0.');
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';
        $factory = new ExtractorResourceNameCollectionFactory(new XmlExtractor([$configPath]));

        $this->assertEquals($factory->create(), new ResourceNameCollection([
            Dummy::class,
            FileConfigDummy::class,
        ]));
    }

    /**
     * @group legacy
     */
    public function testInvalidExtractorResourceNameCollectionFactory()
    {
        $this->expectDeprecation('Using a legacy ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory is deprecated since 2.7 and will not be possible in 3.0.');
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.xml';
        $factory = new ExtractorResourceNameCollectionFactory(new XmlExtractor([$configPath]));
        $factory->create();
    }

    /**
     * @group legacy
     */
    public function testYamlResourceName()
    {
        $this->expectDeprecation('Using a legacy ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory is deprecated since 2.7 and will not be possible in 3.0.');
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';
        $factory = new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath]));

        $this->assertEquals($factory->create(), new ResourceNameCollection([
            Dummy::class,
            FileConfigDummy::class,
        ]));
    }

    /**
     * @group legacy
     */
    public function testYamlSingleResourceName()
    {
        $this->expectDeprecation('Using a legacy ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory is deprecated since 2.7 and will not be possible in 3.0.');
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/single_resource.yml';
        $factory = new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath]));

        $this->assertEquals($factory->create(), new ResourceNameCollection([FileConfigDummy::class]));
    }

    /**
     * @group legacy
     */
    public function testCreateWithMalformedYaml()
    {
        $this->expectDeprecation('Using a legacy ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory is deprecated since 2.7 and will not be possible in 3.0.');
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath])))->create();
    }
}
