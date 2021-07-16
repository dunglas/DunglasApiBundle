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

namespace ApiPlatform\Core\Security;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Checks if the logged user has sufficient permissions to access the given resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ResourceAccessChecker implements ResourceAccessCheckerInterface
{
    private $expressionLanguage;
    private $authenticationTrustResolver;
    private $roleHierarchy;
    private $tokenStorage;
    private $authorizationChecker;
    private $exceptionOnNoToken;

    public function __construct(ExpressionLanguage $expressionLanguage = null, AuthenticationTrustResolverInterface $authenticationTrustResolver = null, RoleHierarchyInterface $roleHierarchy = null, TokenStorageInterface $tokenStorage = null, AuthorizationCheckerInterface $authorizationChecker = null, bool $exceptionOnNoToken = true)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
        $this->roleHierarchy = $roleHierarchy;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->exceptionOnNoToken = $exceptionOnNoToken;
    }

    public function isGranted(string $resourceClass, string $expression, array $extraVariables = []): bool
    {
        if (null === $this->tokenStorage || null === $this->authenticationTrustResolver) {
            throw new \LogicException('The "symfony/security" library must be installed to use the "security" attribute.');
        }
        if (null === $token = $this->tokenStorage->getToken()) {
            if ($this->exceptionOnNoToken) {
                throw new \LogicException('The current token must be set to use the "security" attribute (is the URL behind a firewall?).');
            }

            if (class_exists(NullToken::class)) {
                $token = new NullToken();
            }
        }
        if (null === $this->expressionLanguage) {
            throw new \LogicException('The "symfony/expression-language" library must be installed to use the "security" attribute.');
        }

        $variables = array_merge($extraVariables, [
            'trust_resolver' => $this->authenticationTrustResolver,
            'auth_checker' => $this->authorizationChecker, // needed for the is_granted expression function
        ]);

        if ($token) {
            $variables = array_merge($variables, $this->getVariables($token));
        }

        return (bool) $this->expressionLanguage->evaluate($expression, $variables);
    }

    /**
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Security/Core/Authorization/Voter/ExpressionVoter.php
     */
    private function getVariables(TokenInterface $token): array
    {
        return [
            'token' => $token,
            'user' => $token->getUser(),
            'roles' => $this->getEffectiveRoles($token),
        ];
    }

    /**
     * @return string[]
     */
    private function getEffectiveRoles(TokenInterface $token): array
    {
        if (null === $this->roleHierarchy) {
            return method_exists($token, 'getRoleNames') ? $token->getRoleNames() : array_map('strval', $token->getRoles()); // @phpstan-ignore-line
        }

        if (method_exists($this->roleHierarchy, 'getReachableRoleNames')) {
            return $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());
        }

        return array_map(static function (Role $role): string { // @phpstan-ignore-line
            return $role->getRole(); // @phpstan-ignore-line
        }, $this->roleHierarchy->getReachableRoles($token->getRoles())); // @phpstan-ignore-line
    }
}
