<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Tebaly <admin@freedomsex.net>
 */
final class ApiLoader extends Loader
{
    private $externals;
    private $directories;
    private $resources;

    public function __construct($externals = true, $directories = true, $resources = true)
    {
        $this->externals = $externals;
        $this->directories = $directories;
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null): RouteCollection
    {
        $collection = new RouteCollection();
        if ($this->externals) {
            $collection->addCollection($this->import('.', 'api_external_autoload'));
        }
        if ($this->directories) {
            $collection->addCollection($this->import('.', 'api_directory_autoload'));
        }
        if ($this->resources) {
            $collection->addCollection($this->import('.', 'api_resource_autoload'));
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_platform' === $type;
    }
}
