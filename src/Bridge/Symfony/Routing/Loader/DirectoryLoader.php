<?php


namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;


use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Routing\RouteCollection;

class DirectoryLoader extends Loader
{
    /**
     * {@inheritdoc}
     */
    public function load($path, $type = null)
    {
        $collection = new RouteCollection();
        $collection->addResource(new DirectoryResource($path, '/\.php$/'));
        // todo: need test
        foreach (scandir($path) as $dir) {
            if ('.' !== $dir[0]) {
                $directory = $path.'/'.$dir;
                if (is_dir($directory)) {
                    $subCollection = $this->import($directory, 'api_directory');
                    $collection->addCollection($subCollection);
                }
            }
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_directory' === $type;
    }
}
