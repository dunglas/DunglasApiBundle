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

namespace ApiPlatform\Core\Security\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ExpressionLanguage;
use ApiPlatform\Core\Security\ResourceAccessChecker;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Denies access to the current resource if the logged user doesn't have sufficient permissions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DenyAccessListener
{
    private $resourceMetadataFactory;
    /**
     * @var ResourceAccessCheckerInterface
     */
    private $resourceAccessChecker;

    public function __construct(?ResourceMetadataFactoryInterface $resourceMetadataFactory = null, /*ResourceAccessCheckerInterface*/ $resourceAccessCheckerOrExpressionLanguage = null, AuthenticationTrustResolverInterface $authenticationTrustResolver = null, RoleHierarchyInterface $roleHierarchy = null, TokenStorageInterface $tokenStorage = null, AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($resourceAccessCheckerOrExpressionLanguage instanceof ResourceAccessCheckerInterface) {
            $this->resourceAccessChecker = $resourceAccessCheckerOrExpressionLanguage;

            return;
        }

        $this->resourceAccessChecker = new ResourceAccessChecker($resourceAccessCheckerOrExpressionLanguage, $authenticationTrustResolver, $roleHierarchy, $tokenStorage, $authorizationChecker);
        @trigger_error(sprintf('Passing an instance of "%s" or null as second argument of "%s" is deprecated since API Platform 2.2 and will not be possible anymore in API Platform 3. Pass an instance of "%s" and no extra argument instead.', ExpressionLanguage::class, self::class, ResourceAccessCheckerInterface::class), \E_USER_DEPRECATED);

        if ($resourceMetadataFactory) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0.', ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        @trigger_error(sprintf('Method "%1$s::onKernelRequest" is deprecated since API Platform 2.4 and will not be available anymore in API Platform 3. Prefer calling "%1$s::onSecurity" instead.', self::class), \E_USER_DEPRECATED);
        $this->onSecurityPostDenormalize($event);
    }

    public function onSecurity(RequestEvent $event): void
    {
        $this->checkSecurity($event->getRequest(), 'security', false);
    }

    public function onSecurityPostDenormalize(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->checkSecurity($request, 'security_post_denormalize', true, [
            'previous_object' => $request->attributes->get('previous_data'),
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    private function checkSecurity(Request $request, string $attribute, bool $backwardCompatibility, array $extraVariables = []): void
    {
        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $resourceMetadata = null;
        $isGranted = null;

        if ($this->resourceMetadataFactory) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

            $isGranted = $resourceMetadata->getOperationAttribute($attributes, $attribute, null, true);
            if ($backwardCompatibility && null === $isGranted) {
                // Backward compatibility
                $isGranted = $resourceMetadata->getOperationAttribute($attributes, 'access_control', null, true);
                if (null !== $isGranted) {
                    @trigger_error('Using "access_control" attribute is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Use "security" attribute instead.', \E_USER_DEPRECATED);
                }
            }
        } elseif (isset($attributes['operation'])) {
            $isGranted = $attributes['operation']['security'] ?? null;
        }

        if (null === $isGranted) {
            return;
        }

        $extraVariables += $request->attributes->all();
        $extraVariables['object'] = $request->attributes->get('data');
        $extraVariables['request'] = $request;

        if (!$this->resourceAccessChecker->isGranted($attributes['resource_class'], $isGranted, $extraVariables)) {
            if ($resourceMetadata) {
                throw new AccessDeniedException($resourceMetadata->getOperationAttribute($attributes, $attribute.'_message', 'Access Denied.', true));
            }

            throw new AccessDeniedException($attributes[$attribute.'_message'] ?? 'Access Denied.');
        }
    }
}
