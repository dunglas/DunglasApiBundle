<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Tebaly <admin@freedomsex.net>
 */
final class ResourceClassAutoLoader extends Loader
{
    private $resourceNameCollectionFactory;

    public function __construct(
        ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory
    ) {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $collection = new RouteCollection();
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $subCollection = $this->import($resourceClass, 'api_resource');
            $collection->addCollection($subCollection);
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_resource_autoload' === $type;
    }
}
