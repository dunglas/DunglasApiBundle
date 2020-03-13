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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer;

use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Order as OrderDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Order;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

final class OrderContextFactory implements SerializerContextFactoryInterface
{
    private $decorated;
    private $authorizationChecker;

    public function __construct(SerializerContextFactoryInterface $decorated, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->decorated = $decorated;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function create(string $resourceClass, string $operationName, bool $normalization, array $context): array
    {
        $context = $this->decorated->create($resourceClass, $operationName, $normalization, $context);

        try {
            if (\in_array($resourceClass, [Order::class, OrderDocument::class], true) && isset($context['groups']) && $this->authorizationChecker->isGranted('ROLE_ADMIN')) {
                $context['groups'][] = 'order_admin';
            }
        } catch (AuthenticationCredentialsNotFoundException $exception) {
            return $context;
        }

        return $context;
    }
}
