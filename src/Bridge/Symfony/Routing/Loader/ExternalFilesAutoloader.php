<?php


namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Tebaly <admin@freedomsex.net>
 */
class ExternalFilesAutoloader extends Loader
{
    private $kernel;
    private $formats = [];
    private $graphqlEnabled;
    private $entrypointEnabled;
    private $docsEnabled;
    private $graphiQlEnabled;
    private $graphQlPlaygroundEnabled;

    public function __construct(
        KernelInterface $kernel,
        array $formats,
        bool $graphqlEnabled = false,
        bool $entrypointEnabled = true,
        bool $docsEnabled = true,
        bool $graphiQlEnabled = false,
        bool $graphQlPlaygroundEnabled = false
    ) {
        $this->kernel = $kernel;
        $this->formats = $formats;
        $this->graphqlEnabled = $graphqlEnabled;
        $this->graphiQlEnabled = $graphiQlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->entrypointEnabled = $entrypointEnabled;
        $this->docsEnabled = $docsEnabled;
    }

    /**
     * Load external files.
     */
    public function load($resource, $type = null): RouteCollection
    {
        $collection = new RouteCollection();
        $paths = $this->kernel->locateResource('@ApiPlatformBundle/Resources/config/routing');
        $fileLoader = new XmlFileLoader(
            new FileLocator($paths)
        );

        if ($this->entrypointEnabled) {
            $collection->addCollection($fileLoader->load('api.xml'));
        }
        if ($this->docsEnabled) {
            $collection->addCollection($fileLoader->load('docs.xml'));
        }
        if ($this->graphqlEnabled) {
            $graphqlCollection = $fileLoader->load('graphql/graphql.xml');
            $graphqlCollection->addDefaults(['_graphql' => true]);
            $collection->addCollection($graphqlCollection);
        }
        if ($this->graphiQlEnabled) {
            $graphiQlCollection = $fileLoader->load('graphql/graphiql.xml');
            $graphiQlCollection->addDefaults(['_graphql' => true]);
            $collection->addCollection($graphiQlCollection);
        }
        if ($this->graphQlPlaygroundEnabled) {
            $graphQlPlaygroundCollection = $fileLoader->load('graphql/graphql_playground.xml');
            $graphQlPlaygroundCollection->addDefaults(['_graphql' => true]);
            $collection->addCollection($graphQlPlaygroundCollection);
        }
        if (isset($this->formats['jsonld'])) {
            $collection->addCollection($fileLoader->load('jsonld.xml'));
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_external_autoload' === $type;
    }
}
