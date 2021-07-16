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
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves the operations path using a Symfony route.
 * TODO: remove this in 3.0.
 *
 * @author Guilhem N. <egetick@gmail.com>
 */
final class RouterOperationPathResolver implements OperationPathResolverInterface
{
    private $router;
    private $deferred;

    public function __construct(RouterInterface $router, OperationPathResolverInterface $deferred)
    {
        $this->router = $router;
        $this->deferred = $deferred;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType/*, string $operationName = null*/): string
    {
        if (\func_num_args() >= 4) {
            $operationName = (string) func_get_arg(3);
        } else {
            @trigger_error(sprintf('Method %s() will have a 4th `string $operationName` argument in version 3.0. Not defining it is deprecated since 2.1.', __METHOD__), \E_USER_DEPRECATED);

            $operationName = null;
        }

        if (isset($operation['route_name'])) {
            $routeName = $operation['route_name'];
        } elseif (OperationType::SUBRESOURCE === $operationType) {
            throw new InvalidArgumentException('Subresource operations are not supported by the RouterOperationPathResolver without a route name.');
        } elseif (null === $operationName) {
            return $this->deferred->resolveOperationPath($resourceShortName, $operation, OperationTypeDeprecationHelper::getOperationType($operationType), $operationName);
        } else {
            $routeName = isset($operation['uri_template']) ? $operationName : RouteNameGenerator::generate($operationName, $resourceShortName, $operationType);
        }

        if (!$route = $this->router->getRouteCollection()->get($routeName)) {
            throw new InvalidArgumentException(sprintf('The route "%s" of the resource "%s" was not found.', $routeName, $resourceShortName));
        }

        return $route->getPath();
    }
}
