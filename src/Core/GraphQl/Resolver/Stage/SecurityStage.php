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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Security stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SecurityStage implements SecurityStageInterface
{
    private $resourceMetadataFactory;
    private $resourceAccessChecker;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ?ResourceAccessCheckerInterface $resourceAccessChecker)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceAccessChecker = $resourceAccessChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(string $resourceClass, string $operationName, array $context): void
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $isGranted = $resourceMetadata->getGraphqlAttribute($operationName, 'security', null, true);

        if (null !== $isGranted && null === $this->resourceAccessChecker) {
            throw new \LogicException('Cannot check security expression when SecurityBundle is not installed. Try running "composer require symfony/security-bundle".');
        }

        if (null === $isGranted || $this->resourceAccessChecker->isGranted($resourceClass, (string) $isGranted, $context['extra_variables'])) {
            return;
        }

        throw new AccessDeniedHttpException($resourceMetadata->getGraphqlAttribute($operationName, 'security_message', 'Access Denied.'));
    }
}
