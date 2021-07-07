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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\OperationTypeDeprecationHelper;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RouteNameResolver implements RouteNameResolverInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteName(string $resourceClass, $operationType /*, array $context = [] */): string
    {
        if (\func_num_args() > 2) {
            $context = func_get_arg(2);
        } else {
            $context = [];
        }

        $operationType = OperationTypeDeprecationHelper::getOperationType($operationType);

        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            $currentResourceClass = $route->getDefault('_api_resource_class');
            $operation = $route->getDefault(sprintf('_api_%s_operation_name', $operationType));
            $methods = $route->getMethods();

            if ($resourceClass === $currentResourceClass && null !== $operation && (empty($methods) || \in_array('GET', $methods, true))) {
                if (OperationType::SUBRESOURCE === $operationType && false === $this->isSameSubresource($context, $route->getDefault('_api_subresource_context'))) {
                    continue;
                }

                return $routeName;
            }
        }

        throw new InvalidArgumentException(sprintf('No %s route associated with the type "%s".', $operationType, $resourceClass));
    }

    private function isSameSubresource(array $context, array $currentContext): bool
    {
        $subresources = array_keys($context['subresource_resources']);
        $currentSubresources = [];

        foreach ($currentContext['identifiers'] as [$class]) {
            $currentSubresources[] = $class;
        }

        return $currentSubresources === $subresources;
    }
}
