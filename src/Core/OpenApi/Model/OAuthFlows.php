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

namespace ApiPlatform\Core\OpenApi\Model;

final class OAuthFlows
{
    use ExtensionTrait;

    private $implicit;
    private $password;
    private $clientCredentials;
    private $authorizationCode;

    public function __construct(OAuthFlow $implicit = null, OAuthFlow $password = null, OAuthFlow $clientCredentials = null, OAuthFlow $authorizationCode = null)
    {
        $this->implicit = $implicit;
        $this->password = $password;
        $this->clientCredentials = $clientCredentials;
        $this->authorizationCode = $authorizationCode;
    }

    public function getImplicit(): ?OAuthFlow
    {
        return $this->implicit;
    }

    public function getPassword(): ?OAuthFlow
    {
        return $this->password;
    }

    public function getClientCredentials(): ?OAuthFlow
    {
        return $this->clientCredentials;
    }

    public function getAuthorizationCode(): ?OAuthFlow
    {
        return $this->authorizationCode;
    }

    public function withImplicit(OAuthFlow $implicit): self
    {
        $clone = clone $this;
        $clone->implicit = $implicit;

        return $clone;
    }

    public function withPassword(OAuthFlow $password): self
    {
        $clone = clone $this;
        $clone->password = $password;

        return $clone;
    }

    public function withClientCredentials(OAuthFlow $clientCredentials): self
    {
        $clone = clone $this;
        $clone->clientCredentials = $clientCredentials;

        return $clone;
    }

    public function withAuthorizationCode(OAuthFlow $authorizationCode): self
    {
        $clone = clone $this;
        $clone->authorizationCode = $authorizationCode;

        return $clone;
    }
}
