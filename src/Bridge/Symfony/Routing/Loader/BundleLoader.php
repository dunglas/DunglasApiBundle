<?php


namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;


use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Routing\RouteCollection;

class BundleLoader extends Loader
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
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            if (strpos($resourceClass, $resource) !== false) {
                $subCollection = $this->import($resourceClass, 'api_resource');
                $collection->addCollection($subCollection);
            }
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_bundle' === $type;
    }
}
