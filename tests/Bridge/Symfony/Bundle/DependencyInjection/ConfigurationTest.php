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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Configuration;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Processor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testDefaultConfig()
    {
        $this->runDefaultConfigTests();
    }

    /**
     * @group mongodb
     */
    public function testDefaultConfigWithMongoDbOdm()
    {
        $this->runDefaultConfigTests(['orm', 'odm']);
    }

    private function runDefaultConfigTests(array $doctrineIntegrationsToLoad = ['orm'])
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'title' => 'title',
                'description' => 'description',
                'version' => '1.0.0',
                'doctrine' => [
                    'enabled' => \in_array('orm', $doctrineIntegrationsToLoad, true),
                ],
                'doctrine_mongodb_odm' => [
                    'enabled' => \in_array('odm', $doctrineIntegrationsToLoad, true),
                ],
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'title' => 'title',
            'description' => 'description',
            'version' => '1.0.0',
            'show_webby' => true,
            'formats' => [
                'jsonld' => ['mime_types' => ['application/ld+json']],
                'html' => ['mime_types' => ['text/html']],
            ],
            'error_formats' => [
                'jsonproblem' => ['mime_types' => ['application/problem+json']],
                'jsonld' => ['mime_types' => ['application/ld+json']],
            ],
            'doc_formats' => [
                'json' => ['mime_types' => ['application/json']],
            ],
            'exception_to_status' => [
                ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                FilterValidationException::class => Response::HTTP_BAD_REQUEST,
                OptimisticLockException::class => Response::HTTP_CONFLICT,
            ],
            'default_operation_path_resolver' => 'api_platform.operation_path_resolver.underscore',
            'path_segment_name_generator' => 'api_platform.path_segment_name_generator.underscore',
            'validator' => [
                'serialize_payload_fields' => [],
            ],
            'name_converter' => null,
            'enable_fos_user' => true,
            'enable_nelmio_api_doc' => false,
            'enable_swagger' => true,
            'enable_swagger_ui' => true,
            'enable_entrypoint' => true,
            'enable_re_doc' => true,
            'enable_docs' => true,
            'enable_profiler' => true,
            'graphql' => [
                'enabled' => true,
                'graphiql' => [
                    'enabled' => true,
                ],
            ],
            'elasticsearch' => [
                'enabled' => false,
                'hosts' => [],
                'mapping' => [],
            ],
            'oauth' => [
                'enabled' => false,
                'clientId' => '',
                'clientSecret' => '',
                'type' => 'oauth2',
                'flow' => 'application',
                'tokenUrl' => '/oauth/v2/token',
                'authorizationUrl' => '/oauth/v2/auth',
                'scopes' => [],
            ],
            'swagger' => [
                'api_keys' => [],
            ],
            'eager_loading' => [
                'enabled' => true,
                'max_joins' => 30,
                'force_eager' => true,
                'fetch_partial' => false,
            ],
            'collection' => [
                'exists_parameter_name' => 'exists',
                'order' => 'ASC',
                'order_parameter_name' => 'order',
                'pagination' => [
                    'enabled' => true,
                    'partial' => false,
                    'client_enabled' => false,
                    'client_items_per_page' => false,
                    'client_partial' => false,
                    'items_per_page' => 30,
                    'page_parameter_name' => 'page',
                    'enabled_parameter_name' => 'pagination',
                    'items_per_page_parameter_name' => 'itemsPerPage',
                    'partial_parameter_name' => 'partial',
                    'maximum_items_per_page' => null,
                ],
            ],
            'mapping' => [
                'paths' => [],
            ],
            'http_cache' => [
                'invalidation' => [
                    'enabled' => false,
                    'varnish_urls' => [],
                    'request_options' => [],
                ],
                'etag' => true,
                'max_age' => null,
                'shared_max_age' => null,
                'vary' => ['Accept'],
                'public' => null,
            ],
            'doctrine' => [
                'enabled' => \in_array('orm', $doctrineIntegrationsToLoad, true),
            ],
            'doctrine_mongodb_odm' => [
                'enabled' => \in_array('odm', $doctrineIntegrationsToLoad, true),
            ],
            'messenger' => [
                'enabled' => true,
            ],
            'mercure' => [
                'enabled' => true,
                'hub_url' => null,
            ],
            'allow_plain_identifiers' => false,
            'resource_class_directories' => [],
        ], $config);
    }

    /**
     * @group legacy
     * @expectedDeprecation Using a string "HTTP_INTERNAL_SERVER_ERROR" as a constant of the "Symfony\Component\HttpFoundation\Response" class is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3. Use the Symfony's custom YAML extension for PHP constants instead (i.e. "!php/const Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR").
     */
    public function testLegacyExceptionToStatusConfig()
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                    \RuntimeException::class => 'HTTP_INTERNAL_SERVER_ERROR',
                ],
            ],
        ]);

        $this->assertTrue(isset($config['exception_to_status']));
        $this->assertSame([
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            \RuntimeException::class => Response::HTTP_INTERNAL_SERVER_ERROR,
        ], $config['exception_to_status']);
    }

    /**
     * @group legacy
     * @expectedDeprecation The use of the `default_operation_path_resolver` has been deprecated in 2.1 and will be removed in 3.0. Use `path_segment_name_generator` instead.
     */
    public function testLegacyDefaultOperationPathResolver()
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'default_operation_path_resolver' => 'api_platform.operation_path_resolver.dash',
            ],
        ]);

        $this->assertTrue(isset($config['default_operation_path_resolver']));
    }

    public function invalidHttpStatusCodeProvider()
    {
        return [
            [0],
            [99],
            [700],
            [1000],
        ];
    }

    /**
     * @dataProvider invalidHttpStatusCodeProvider
     */
    public function testExceptionToStatusConfigWithInvalidHttpStatusCode($invalidHttpStatusCode)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageRegExp('/The HTTP status code ".+" is not valid\\./');

        $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \Exception::class => $invalidHttpStatusCode,
                ],
            ],
        ]);
    }

    public function invalidHttpStatusCodeValueProvider()
    {
        return [
            [true],
            [null],
            [-INF],
            [40.4],
            ['foo'],
            ['HTTP_FOO_BAR'],
        ];
    }

    /**
     * @dataProvider invalidHttpStatusCodeValueProvider
     */
    public function testExceptionToStatusConfigWithInvalidHttpStatusCodeValue($invalidHttpStatusCodeValue)
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageRegExp('/Invalid type for path "api_platform\\.exception_to_status\\.Exception". Expected int, but got .+\\./');

        $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \Exception::class => $invalidHttpStatusCodeValue,
                ],
            ],
        ]);
    }

    /**
     * Test config for api keys.
     */
    public function testApiKeysConfig()
    {
        $exampleConfig = [
            'name' => 'Authorization',
            'type' => 'query',
        ];

        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'swagger' => [
                    'api_keys' => [$exampleConfig],
                ],
            ],
        ]);

        $this->assertTrue(isset($config['swagger']['api_keys']));
        $this->assertSame($exampleConfig, $config['swagger']['api_keys'][0]);
    }

    /**
     * Test config for empty title and description.
     */
    public function testEmptyTitleDescriptionConfig()
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [],
        ]);

        $this->assertSame('', $config['title']);
        $this->assertSame('', $config['description']);
    }

    public function testEnableElasticsearch()
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'elasticsearch' => true,
            ],
        ]);

        $this->assertTrue($config['elasticsearch']['enabled']);
    }
}
