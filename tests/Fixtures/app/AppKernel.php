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

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\User as UserDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\Common\Inflector\Inflector;
use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactory;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * AppKernel for tests.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        // patch for behat/symfony2-extension not supporting %env(APP_ENV)%
        $this->environment = $_SERVER['APP_ENV'] ?? $environment;

        // patch for old versions of Doctrine Inflector, to delete when we'll drop support for v1
        // see https://github.com/doctrine/inflector/issues/147#issuecomment-628807276
        if (class_exists(Inflector::class)) {
            Inflector::rules('plural', ['/taxon/i' => 'taxa']);
        }
    }

    public function registerBundles(): array
    {
        $bundles = [
            new ApiPlatformBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new MercureBundle(),
            new SecurityBundle(),
            new WebProfilerBundle(),
            new FriendsOfBehatSymfonyExtensionBundle(),
            new FrameworkBundle(),
        ];

        if (class_exists(DoctrineMongoDBBundle::class)) {
            $bundles[] = new DoctrineMongoDBBundle();
        }

        if (class_exists(NelmioApiDocBundle::class)) {
            $bundles[] = new NelmioApiDocBundle();
        }

        if ('elasticsearch' !== $this->getEnvironment()) {
            $bundles[] = new TestBundle();
        }

        return $bundles;
    }

    public function getProjectDir()
    {
        return __DIR__;
    }

    /**
     * @param RoutingConfigurator|RouteCollectionBuilder $routes
     */
    protected function configureRoutes($routes)
    {
        $routes->import(__DIR__."/config/routing_{$this->getEnvironment()}.yml");

        if (class_exists(NelmioApiDocBundle::class)) {
            $routes->import('@NelmioApiDocBundle/Resources/config/routing.yml', '/nelmioapidoc');
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.project_dir', __DIR__);

        $loader->load(__DIR__."/config/config_{$this->getEnvironment()}.yml");

        /* @TODO remove this check in 3.0 */
        if (\PHP_VERSION_ID >= 70200 && class_exists(Uuid::class) && class_exists(UuidType::class)) {
            $loader->load(__DIR__.'/config/config_symfony_uid.yml');
        }

        $c->prependExtensionConfig('framework', [
            'secret' => 'dunglas.fr',
            'validation' => ['enable_annotations' => true],
            'serializer' => ['enable_annotations' => true],
            'test' => null,
            'session' => class_exists(SessionFactory::class) ? ['storage_factory_id' => 'session.storage.factory.mock_file'] : ['storage_id' => 'session.storage.mock_file'],
            'profiler' => [
                'enabled' => true,
                'collect' => false,
            ],
            'messenger' => [
                'default_bus' => 'messenger.bus.default',
                'buses' => [
                    'messenger.bus.default' => ['default_middleware' => 'allow_no_handlers'],
                ],
            ],
            'router' => ['utf8' => true],
        ]);

        $alg = class_exists(NativePasswordHasher::class) || class_exists('Symfony\Component\Security\Core\Encoder\NativePasswordEncoder') ? 'auto' : 'bcrypt';
        $securityConfig = [
            'encoders' => [
                User::class => $alg,
                UserDocument::class => $alg,
                // Don't use plaintext in production!
                UserInterface::class => 'plaintext',
            ],
            'providers' => [
                'chain_provider' => [
                    'chain' => [
                        'providers' => ['in_memory', 'entity'],
                    ],
                ],
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'dunglas' => ['password' => 'kevin', 'roles' => 'ROLE_USER'],
                            'admin' => ['password' => 'kitten', 'roles' => 'ROLE_ADMIN'],
                        ],
                    ],
                ],
                'entity' => [
                    'entity' => [
                        'class' => User::class,
                        'property' => 'email',
                    ],
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt|error)|css|images|js)/',
                    'security' => false,
                ],
                'default' => [
                    'provider' => 'chain_provider',
                    'http_basic' => null,
                    'anonymous' => null,
                    'stateless' => true,
                ],
            ],
            'access_control' => [
                ['path' => '^/', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ],
        ];

        $c->prependExtensionConfig('security', $securityConfig);

        if (class_exists(DoctrineMongoDBBundle::class)) {
            $c->prependExtensionConfig('doctrine_mongodb', [
                'connections' => [
                    'default' => null,
                ],
                'document_managers' => [
                    'default' => [
                        'auto_mapping' => true,
                    ],
                ],
            ]);
        }

        $twigConfig = ['strict_variables' => '%kernel.debug%'];
        if (interface_exists(ErrorRendererInterface::class)) {
            $twigConfig['exception_controller'] = null;
        }
        $c->prependExtensionConfig('twig', $twigConfig);

        if (class_exists(NelmioApiDocBundle::class)) {
            $c->prependExtensionConfig('nelmio_api_doc', [
                'sandbox' => [
                    'accept_type' => 'application/json',
                    'body_format' => [
                        'formats' => ['json'],
                        'default_format' => 'json',
                    ],
                    'request_format' => [
                        'formats' => ['json' => 'application/json'],
                    ],
                ],
            ]);
            $c->prependExtensionConfig('api_platform', ['enable_nelmio_api_doc' => true]);
        }
    }
}
