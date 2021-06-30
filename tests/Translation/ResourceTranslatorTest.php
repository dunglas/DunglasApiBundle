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

namespace ApiPlatform\Core\Tests\Translation;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTranslatable;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTranslation;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Core\Translation\ResourceTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ResourceTranslatorTest extends TestCase
{
    use ProphecyTrait;

    private $requestStack;
    private $resourceMetadataFactoryProphecy;
    private $resourceTranslator;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->resourceTranslator = new ResourceTranslator($this->requestStack, $propertyAccessor, $this->resourceMetadataFactoryProphecy->reveal());
    }

    /**
     * @dataProvider provideIsResourceTranslatableCases
     */
    public function testIsResourceTranslatable($resource, bool $expected): void
    {
        self::assertSame($expected, $this->resourceTranslator->isResourceTranslatable($resource));
    }

    public function provideIsResourceTranslatableCases(): iterable
    {
        yield 'translatable resource' => [new DummyTranslatable(), true];
        yield 'not translatable resource' => [new Dummy(), false];
    }

    /**
     * @dataProvider provideIsResourceClassTranslatableCases
     */
    public function testIsResourceClassTranslatable(string $resourceClass, bool $expected): void
    {
        self::assertSame($expected, $this->resourceTranslator->isResourceClassTranslatable($resourceClass));
    }

    public function provideIsResourceClassTranslatableCases(): iterable
    {
        yield 'translatable resource class' => [DummyTranslatable::class, true];
        yield 'not translatable resource class' => [Dummy::class, false];
    }

    public function testIsAllTranslationsEnabledDisabled(): void
    {
        $this->resourceMetadataFactoryProphecy->create(DummyTranslatable::class)->willReturn(new ResourceMetadata());

        self::assertFalse($this->resourceTranslator->isAllTranslationsEnabled(DummyTranslatable::class, []));
    }

    public function testIsAllTranslationsEnabledResourceEnabled(): void
    {
        $resourceClass = DummyTranslatable::class;
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['translation' => ['allTranslationsEnabled' => true]]);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        self::assertTrue($this->resourceTranslator->isAllTranslationsEnabled($resourceClass, []));
    }

    public function testIsAllTranslationsEnabledClientEnabled(): void
    {
        $resourceClass = DummyTranslatable::class;
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['translation' => ['allTranslationsClientEnabled' => true]]);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        self::assertTrue($this->resourceTranslator->isAllTranslationsEnabled($resourceClass, ['allTranslations' => true]));
    }

    public function testIsAllTranslationsEnabledClientEnabledCustomParameter(): void
    {
        $resourceClass = DummyTranslatable::class;
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['translation' => ['allTranslationsClientEnabled' => true, 'allTranslationsClientParameterName' => 'allT']]);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        self::assertTrue($this->resourceTranslator->isAllTranslationsEnabled($resourceClass, ['allT' => true]));
    }

    public function testIsAllTranslationsEnabledClientDisabled(): void
    {
        $resourceClass = DummyTranslatable::class;
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['translation' => ['allTranslationsClientEnabled' => true]]);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        self::assertFalse($this->resourceTranslator->isAllTranslationsEnabled($resourceClass, ['allTranslations' => false]));
    }

    public function testIsAllTranslationsEnabledResourceEnabledClientDisabled(): void
    {
        $resourceClass = DummyTranslatable::class;
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['translation' => ['allTranslationsEnabled' => true, 'allTranslationsClientEnabled' => true]]);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        self::assertFalse($this->resourceTranslator->isAllTranslationsEnabled($resourceClass, ['allTranslations' => false]));
    }

    public function testGetTranslationClassNoTranslationClass(): void
    {
        $resourceClass = DummyTranslatable::class;
        $resourceMetadata = new ResourceMetadata();

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "class" attribute must be defined in the "translation" configuration of the resource "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyTranslatable".');

        $this->resourceTranslator->getTranslationClass($resourceClass);
    }

    public function testGetTranslationClass(): void
    {
        $resourceClass = DummyTranslatable::class;
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withAttributes(['translation' => ['class' => DummyTranslation::class]]);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        self::assertSame(DummyTranslation::class, $this->resourceTranslator->getTranslationClass($resourceClass));
    }

    public function testGetLocaleNoRequest(): void
    {
        self::assertNull($this->resourceTranslator->getLocale());
    }

    public function testGetLocale(): void
    {
        $locale = 'fr';

        $request = new Request();
        $request->setLocale($locale);
        $this->requestStack->push($request);

        self::assertSame($locale, $this->resourceTranslator->getLocale());
    }

    /**
     * @dataProvider provideTranslateAttributeValueCases
     */
    public function testTranslateAttributeValue($resource, string $attribute, array $context, ?string $requestLocale, $expectedTranslated): void
    {
        if ($requestLocale) {
            $request = new Request();
            $request->setLocale($requestLocale);
            $this->requestStack->push($request);
        }

        self::assertSame($expectedTranslated, $this->resourceTranslator->translateAttributeValue($resource, $attribute, $context));
    }

    public function provideTranslateAttributeValueCases(): iterable
    {
        $locale = 'fr';

        yield 'not translatable resource' => [new Dummy(), 'foo', [], null, null];
        yield 'no request' => [new DummyTranslatable(), 'foo', [], null, null];
        yield 'no translation' => [new DummyTranslatable(), 'foo', [], $locale, null];

        $dummyTranslatable = new DummyTranslatable();
        $dummyTranslation = new DummyTranslation();
        $dummyTranslation->name = null;
        $dummyTranslation->locale = $locale;
        $dummyTranslatable->addResourceTranslation($dummyTranslation);

        yield 'non existent attribute' => [$dummyTranslatable, 'foo', [], $locale, null];
        yield 'null attribute' => [$dummyTranslatable, 'name', [], $locale, null];

        $dummyTranslatable = new DummyTranslatable();
        $dummyTranslation = new DummyTranslation();
        $dummyTranslation->name = 'Nom traduit';
        $dummyTranslation->locale = $locale;
        $dummyTranslatable->addResourceTranslation($dummyTranslation);

        yield 'existent attribute' => [$dummyTranslatable, 'name', [], $locale, 'Nom traduit'];

        $dummyTranslatable = new DummyTranslatable();
        $dummyTranslationFr = new DummyTranslation();
        $dummyTranslationFr->name = 'Nom traduit';
        $dummyTranslationFr->locale = 'fr';
        $dummyTranslatable->addResourceTranslation($dummyTranslationFr);
        $dummyTranslationEn = new DummyTranslation();
        $dummyTranslationEn->name = 'Name translated';
        $dummyTranslationEn->locale = 'en';
        $dummyTranslatable->addResourceTranslation($dummyTranslationEn);

        yield 'all translations' => [$dummyTranslatable, 'name', ['all_translations_enabled' => true], null, ['fr' => 'Nom traduit', 'en' => 'Name translated']];
    }

    public function testTranslateAttributeValueBadType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Attribute "id" needs to be a string but is of type "integer".');

        $request = new Request();
        $request->setLocale('fr');
        $this->requestStack->push($request);

        $dummyTranslatable = new DummyTranslatable();
        $dummyTranslation = new DummyTranslation();
        $dummyTranslation->id = 3;
        $dummyTranslation->locale = 'fr';
        $dummyTranslatable->addResourceTranslation($dummyTranslation);

        $this->resourceTranslator->translateAttributeValue($dummyTranslatable, 'id', []);
    }
}
