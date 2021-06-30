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

namespace ApiPlatform\Core\Tests\Annotation;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Tests\Fixtures\AnnotatedClass;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyPhp8;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiResourceTest extends TestCase
{
    public function testConstruct()
    {
        $resource = new ApiResource([
            'security' => 'is_granted("ROLE_FOO")',
            'securityMessage' => 'You are not foo.',
            'securityPostDenormalize' => 'is_granted("ROLE_BAR")',
            'securityPostDenormalizeMessage' => 'You are not bar.',
            'attributes' => ['foo' => 'bar', 'validation_groups' => ['baz', 'qux'], 'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0, 'vary' => ['Custom-Vary-1', 'Custom-Vary-2']]],
            'collectionOperations' => ['bar' => ['foo']],
            'denormalizationContext' => ['groups' => ['foo']],
            'description' => 'description',
            'fetchPartial' => true,
            'forceEager' => false,
            'formats' => ['foo', 'bar' => ['application/bar']],
            'filters' => ['foo', 'bar'],
            'graphql' => ['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]],
            'input' => 'Foo',
            'iri' => 'http://example.com/res',
            'itemOperations' => ['foo' => ['bar']],
            'mercure' => ['private' => true],
            'messenger' => true,
            'normalizationContext' => ['groups' => ['bar']],
            'order' => ['foo', 'bar' => 'ASC'],
            'openapiContext' => ['description' => 'foo'],
            'output' => 'Bar',
            'paginationClientEnabled' => true,
            'paginationClientItemsPerPage' => true,
            'paginationClientPartial' => true,
            'paginationEnabled' => true,
            'paginationFetchJoinCollection' => true,
            'paginationItemsPerPage' => 42,
            'paginationMaximumItemsPerPage' => 50,
            'paginationPartial' => true,
            'routePrefix' => '/foo',
            'shortName' => 'shortName',
            'subresourceOperations' => [],
            'swaggerContext' => ['description' => 'bar'],
            'validationGroups' => ['foo', 'bar'],
            'sunset' => 'Thu, 11 Oct 2018 00:00:00 +0200',
            'urlGenerationStrategy' => UrlGeneratorInterface::ABS_PATH,
            'queryParameterValidationEnabled' => false,
            'translation' => ['class' => 'TranslationClass'],
        ]);

        $this->assertSame('shortName', $resource->shortName);
        $this->assertSame('description', $resource->description);
        $this->assertSame('http://example.com/res', $resource->iri);
        $this->assertSame(['foo' => ['bar']], $resource->itemOperations);
        $this->assertSame(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertSame([], $resource->subresourceOperations);
        $this->assertSame(['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]], $resource->graphql);
        $this->assertEquals([
            'security' => 'is_granted("ROLE_FOO")',
            'security_message' => 'You are not foo.',
            'security_post_denormalize' => 'is_granted("ROLE_BAR")',
            'security_post_denormalize_message' => 'You are not bar.',
            'denormalization_context' => ['groups' => ['foo']],
            'fetch_partial' => true,
            'foo' => 'bar',
            'force_eager' => false,
            'formats' => ['foo', 'bar' => ['application/bar']],
            'filters' => ['foo', 'bar'],
            'input' => 'Foo',
            'mercure' => ['private' => true],
            'messenger' => true,
            'normalization_context' => ['groups' => ['bar']],
            'order' => ['foo', 'bar' => 'ASC'],
            'openapi_context' => ['description' => 'foo'],
            'output' => 'Bar',
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => true,
            'pagination_client_partial' => true,
            'pagination_enabled' => true,
            'pagination_fetch_join_collection' => true,
            'pagination_items_per_page' => 42,
            'pagination_maximum_items_per_page' => 50,
            'pagination_partial' => true,
            'route_prefix' => '/foo',
            'swagger_context' => ['description' => 'bar'],
            'validation_groups' => ['baz', 'qux'],
            'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0, 'vary' => ['Custom-Vary-1', 'Custom-Vary-2']],
            'sunset' => 'Thu, 11 Oct 2018 00:00:00 +0200',
            'url_generation_strategy' => 1,
            'query_parameter_validation_enabled' => false,
            'translation' => ['class' => 'TranslationClass'],
        ], $resource->attributes);
    }

    /**
     * @requires PHP 8.0
     */
    public function testConstructAttribute()
    {
        $resource = eval(<<<'PHP'
return new \ApiPlatform\Core\Annotation\ApiResource(
    security:  'is_granted("ROLE_FOO")',
    securityMessage:  'You are not foo.',
    securityPostDenormalize:  'is_granted("ROLE_BAR")',
    securityPostDenormalizeMessage:  'You are not bar.',
    attributes:  ['foo' => 'bar', 'validation_groups' => ['baz', 'qux'], 'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0, 'vary' => ['Custom-Vary-1', 'Custom-Vary-2']]],
    collectionOperations:  ['bar' => ['foo']],
    denormalizationContext:  ['groups' => ['foo']],
    description:  'description',
    fetchPartial:  true,
    forceEager:  false,
    formats:  ['foo', 'bar' => ['application/bar']],
    filters:  ['foo', 'bar'],
    graphql:  ['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]],
    input:  'Foo',
    iri:  'http://example.com/res',
    itemOperations:  ['foo' => ['bar']],
    mercure:  ['private' => true],
    messenger:  true,
    normalizationContext:  ['groups' => ['bar']],
    order:  ['foo', 'bar' => 'ASC'],
    openapiContext:  ['description' => 'foo'],
    output:  'Bar',
    paginationClientEnabled:  true,
    paginationClientItemsPerPage:  true,
    paginationClientPartial:  true,
    paginationEnabled:  true,
    paginationFetchJoinCollection:  true,
    paginationItemsPerPage:  42,
    paginationMaximumItemsPerPage:  50,
    paginationPartial:  true,
    routePrefix:  '/foo',
    shortName:  'shortName',
    subresourceOperations:  [],
    swaggerContext:  ['description' => 'bar'],
    validationGroups:  ['foo', 'bar'],
    sunset:  'Thu, 11 Oct 2018 00:00:00 +0200',
    urlGenerationStrategy:  \ApiPlatform\Core\Api\UrlGeneratorInterface::ABS_PATH,
    deprecationReason: 'reason',
    elasticsearch: true,
    hydraContext: ['hydra' => 'foo'],
    paginationViaCursor: ['foo'],
    stateless: true,
    exceptionToStatus: [
        \DomainException::class => 400,
    ],
    queryParameterValidationEnabled: false,
    translation: ['class' => 'TranslationClass'],
);
PHP
        );

        $this->assertSame('shortName', $resource->shortName);
        $this->assertSame('description', $resource->description);
        $this->assertSame('http://example.com/res', $resource->iri);
        $this->assertSame(['foo' => ['bar']], $resource->itemOperations);
        $this->assertSame(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertSame([], $resource->subresourceOperations);
        $this->assertSame(['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]], $resource->graphql);
        $this->assertEquals([
            'security' => 'is_granted("ROLE_FOO")',
            'security_message' => 'You are not foo.',
            'security_post_denormalize' => 'is_granted("ROLE_BAR")',
            'security_post_denormalize_message' => 'You are not bar.',
            'denormalization_context' => ['groups' => ['foo']],
            'fetch_partial' => true,
            'foo' => 'bar',
            'force_eager' => false,
            'formats' => ['foo', 'bar' => ['application/bar']],
            'filters' => ['foo', 'bar'],
            'input' => 'Foo',
            'mercure' => ['private' => true],
            'messenger' => true,
            'normalization_context' => ['groups' => ['bar']],
            'order' => ['foo', 'bar' => 'ASC'],
            'openapi_context' => ['description' => 'foo'],
            'output' => 'Bar',
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => true,
            'pagination_client_partial' => true,
            'pagination_enabled' => true,
            'pagination_fetch_join_collection' => true,
            'pagination_items_per_page' => 42,
            'pagination_maximum_items_per_page' => 50,
            'pagination_partial' => true,
            'route_prefix' => '/foo',
            'swagger_context' => ['description' => 'bar'],
            'validation_groups' => ['baz', 'qux'],
            'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0, 'vary' => ['Custom-Vary-1', 'Custom-Vary-2']],
            'sunset' => 'Thu, 11 Oct 2018 00:00:00 +0200',
            'url_generation_strategy' => 1,
            'deprecation_reason' => 'reason',
            'elasticsearch' => true,
            'hydra_context' => ['hydra' => 'foo'],
            'pagination_via_cursor' => ['foo'],
            'stateless' => true,
            'composite_identifier' => null,
            'exception_to_status' => [
                \DomainException::class => 400,
            ],
            'query_parameter_validation_enabled' => false,
            'translation' => ['class' => 'TranslationClass'],
        ], $resource->attributes);
    }

    /**
     * @requires PHP 8.0
     */
    public function testUseAttribute()
    {
        $this->assertSame('Hey PHP 8', (new \ReflectionClass(DummyPhp8::class))->getAttributes(ApiResource::class)[0]->getArguments()['description']);
    }

    public function testApiResourceAnnotation()
    {
        $reader = new AnnotationReader();
        /**
         * @var ApiResource
         */
        $resource = $reader->getClassAnnotation(new \ReflectionClass(AnnotatedClass::class), ApiResource::class);

        $this->assertSame('shortName', $resource->shortName);
        $this->assertSame('description', $resource->description);
        $this->assertSame('http://example.com/res', $resource->iri);
        $this->assertSame(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertSame(['query' => ['normalization_context' => ['groups' => ['foo', 'bar']]]], $resource->graphql);
        $this->assertEquals([
            'foo' => 'bar',
            'route_prefix' => '/whatever',
            'security' => "is_granted('ROLE_FOO')",
            'security_message' => 'You are not foo.',
            'security_post_denormalize' => "is_granted('ROLE_BAR')",
            'security_post_denormalize_message' => 'You are not bar.',
            'cache_headers' => ['max_age' => 0, 'shared_max_age' => 0, 'vary' => ['Custom-Vary-1', 'Custom-Vary-2']],
        ], $resource->attributes);
    }

    public function testConstructWithInvalidAttribute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property "invalidAttribute" on annotation "ApiPlatform\\Core\\Annotation\\ApiResource".');

        new ApiResource([
            'shortName' => 'shortName',
            'routePrefix' => '/foo',
            'invalidAttribute' => 'exception',
        ]);
    }

    /**
     * @group legacy
     * @expectedDeprecation Attribute "accessControl" is deprecated in annotation since API Platform 2.5, prefer using "security" attribute instead
     * @expectedDeprecation Attribute "accessControlMessage" is deprecated in annotation since API Platform 2.5, prefer using "securityMessage" attribute instead
     */
    public function testWithDeprecatedAttributes()
    {
        new ApiResource([
            'accessControl' => "is_granted('ROLE_USER')",
            'accessControlMessage' => 'Nope!',
        ]);
    }
}
