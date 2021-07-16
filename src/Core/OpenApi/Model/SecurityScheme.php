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

final class SecurityScheme
{
    use ExtensionTrait;

    private $type;
    private $description;
    private $name;
    private $in;
    private $scheme;
    private $bearerFormat;
    private $flows;
    private $openIdConnectUrl;

    public function __construct(string $type = null, string $description = '', string $name = null, string $in = null, string $scheme = null, string $bearerFormat = null, OAuthFlows $flows = null, string $openIdConnectUrl = null)
    {
        $this->type = $type;
        $this->description = $description;
        $this->name = $name;
        $this->in = $in;
        $this->scheme = $scheme;
        $this->bearerFormat = $bearerFormat;
        $this->flows = $flows;
        $this->openIdConnectUrl = $openIdConnectUrl;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIn(): ?string
    {
        return $this->in;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function getBearerFormat(): ?string
    {
        return $this->bearerFormat;
    }

    public function getFlows(): ?OAuthFlows
    {
        return $this->flows;
    }

    public function getOpenIdConnectUrl(): ?string
    {
        return $this->openIdConnectUrl;
    }

    public function withType(string $type): self
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function withIn(string $in): self
    {
        $clone = clone $this;
        $clone->in = $in;

        return $clone;
    }

    public function withScheme(string $scheme): self
    {
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    public function withBearerFormat(string $bearerFormat): self
    {
        $clone = clone $this;
        $clone->bearerFormat = $bearerFormat;

        return $clone;
    }

    public function withFlows(OAuthFlows $flows): self
    {
        $clone = clone $this;
        $clone->flows = $flows;

        return $clone;
    }

    public function withOpenIdConnectUrl(string $openIdConnectUrl): self
    {
        $clone = clone $this;
        $clone->openIdConnectUrl = $openIdConnectUrl;

        return $clone;
    }
}
