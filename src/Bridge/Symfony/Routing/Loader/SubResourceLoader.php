<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Tebaly <admin@freedomsex.net>
 */
final class SubResourceLoader extends Loader
{
    public const DEFAULT_ACTION_PATTERN = 'api_platform.action.';

    private $container;
    private $subresourceOperationFactory;

    public function __construct(
        ContainerInterface $container,
        SubresourceOperationFactoryInterface $subresourceOperationFactory
    ) {
        $this->container = $container;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resourceClass, $type = null): RouteCollection
    {
        $routeCollection = new RouteCollection();

        foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $operation) {
            $controller = $operation['controller'] ?? null;
            if (null === $controller) {
                $controller = self::DEFAULT_ACTION_PATTERN.'get_subresource';

                if (!$this->container->has($controller)) {
                    throw new RuntimeException(sprintf('There is no builtin action for the %s %s operation. You need to define the controller yourself.', OperationType::SUBRESOURCE, 'GET'));
                }
            }

            $routeCollection->add($operation['route_name'], new Route(
                $operation['path'],
                [
                    '_controller' => $controller,
                    '_format' => null,
                    '_api_resource_class' => $operation['resource_class'],
                    '_api_subresource_operation_name' => $operation['route_name'],
                    '_api_subresource_context' => [
                        'property' => $operation['property'],
                        'identifiers' => $operation['identifiers'],
                        'collection' => $operation['collection'],
                        'operationId' => $operationId,
                    ],
                ] + ($operation['defaults'] ?? []),
                $operation['requirements'] ?? [],
                $operation['options'] ?? [],
                $operation['host'] ?? '',
                $operation['schemes'] ?? [],
                ['GET'],
                $operation['condition'] ?? ''
            ));
        }

        return $routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        if (null !== $this->subresourceOperationFactory) {
            return 'api_subresource' === $type;
        }
    }

}
