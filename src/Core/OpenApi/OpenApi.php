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

namespace ApiPlatform\Core\OpenApi;

use ApiPlatform\Core\Documentation\DocumentationInterface;
use ApiPlatform\Core\OpenApi\Model\Components;
use ApiPlatform\Core\OpenApi\Model\ExtensionTrait;
use ApiPlatform\Core\OpenApi\Model\Info;
use ApiPlatform\Core\OpenApi\Model\Paths;

final class OpenApi implements DocumentationInterface
{
    use ExtensionTrait;

    public const VERSION = '3.0.3';

    private $openapi;
    private $info;
    private $servers;
    private $paths;
    private $components;
    private $security;
    private $tags;
    private $externalDocs;

    public function __construct(Info $info, array $servers, Paths $paths, Components $components = null, array $security = [], array $tags = [], $externalDocs = null)
    {
        $this->openapi = self::VERSION;
        $this->info = $info;
        $this->servers = $servers;
        $this->paths = $paths;
        $this->components = $components;
        $this->security = $security;
        $this->tags = $tags;
        $this->externalDocs = $externalDocs;
    }

    public function getOpenapi(): string
    {
        return $this->openapi;
    }

    public function getInfo(): Info
    {
        return $this->info;
    }

    public function getServers(): array
    {
        return $this->servers;
    }

    public function getPaths(): Paths
    {
        return $this->paths;
    }

    public function getComponents(): Components
    {
        return $this->components;
    }

    public function getSecurity(): array
    {
        return $this->security;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getExternalDocs(): ?array
    {
        return $this->externalDocs;
    }

    public function withOpenapi(string $openapi): self
    {
        $clone = clone $this;
        $clone->openapi = $openapi;

        return $clone;
    }

    public function withInfo(Info $info): self
    {
        $clone = clone $this;
        $clone->info = $info;

        return $clone;
    }

    public function withServers(array $servers): self
    {
        $clone = clone $this;
        $clone->servers = $servers;

        return $clone;
    }

    public function withPaths(Paths $paths): self
    {
        $clone = clone $this;
        $clone->paths = $paths;

        return $clone;
    }

    public function withComponents(Components $components): self
    {
        $clone = clone $this;
        $clone->components = $components;

        return $clone;
    }

    public function withSecurity(array $security): self
    {
        $clone = clone $this;
        $clone->security = $security;

        return $clone;
    }

    public function withTags(array $tags): self
    {
        $clone = clone $this;
        $clone->tags = $tags;

        return $clone;
    }

    public function withExternalDocs(array $externalDocs): self
    {
        $clone = clone $this;
        $clone->externalDocs = $externalDocs;

        return $clone;
    }
}
