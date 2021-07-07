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

final class PathItem
{
    use ExtensionTrait;

    public static $methods = ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH', 'TRACE'];
    private $ref;
    private $summary;
    private $description;
    private $get;
    private $put;
    private $post;
    private $delete;
    private $options;
    private $head;
    private $patch;
    private $trace;
    private $servers;
    private $parameters;

    public function __construct(string $ref = null, string $summary = null, string $description = null, Operation $get = null, Operation $put = null, Operation $post = null, Operation $delete = null, Operation $options = null, Operation $head = null, Operation $patch = null, Operation $trace = null, ?array $servers = null, array $parameters = [])
    {
        $this->ref = $ref;
        $this->summary = $summary;
        $this->description = $description;
        $this->get = $get;
        $this->put = $put;
        $this->post = $post;
        $this->delete = $delete;
        $this->options = $options;
        $this->head = $head;
        $this->patch = $patch;
        $this->trace = $trace;
        $this->servers = $servers;
        $this->parameters = $parameters;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getGet(): ?Operation
    {
        return $this->get;
    }

    public function getPut(): ?Operation
    {
        return $this->put;
    }

    public function getPost(): ?Operation
    {
        return $this->post;
    }

    public function getDelete(): ?Operation
    {
        return $this->delete;
    }

    public function getOptions(): ?Operation
    {
        return $this->options;
    }

    public function getHead(): ?Operation
    {
        return $this->head;
    }

    public function getPatch(): ?Operation
    {
        return $this->patch;
    }

    public function getTrace(): ?Operation
    {
        return $this->trace;
    }

    public function getServers(): ?array
    {
        return $this->servers;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function withRef(string $ref): self
    {
        $clone = clone $this;
        $clone->ref = $ref;

        return $clone;
    }

    public function withSummary(string $summary): self
    {
        $clone = clone $this;
        $clone->summary = $summary;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withGet(?Operation $get): self
    {
        $clone = clone $this;
        $clone->get = $get;

        return $clone;
    }

    public function withPut(?Operation $put): self
    {
        $clone = clone $this;
        $clone->put = $put;

        return $clone;
    }

    public function withPost(?Operation $post): self
    {
        $clone = clone $this;
        $clone->post = $post;

        return $clone;
    }

    public function withDelete(?Operation $delete): self
    {
        $clone = clone $this;
        $clone->delete = $delete;

        return $clone;
    }

    public function withOptions(Operation $options): self
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    public function withHead(Operation $head): self
    {
        $clone = clone $this;
        $clone->head = $head;

        return $clone;
    }

    public function withPatch(?Operation $patch): self
    {
        $clone = clone $this;
        $clone->patch = $patch;

        return $clone;
    }

    public function withTrace(Operation $trace): self
    {
        $clone = clone $this;
        $clone->trace = $trace;

        return $clone;
    }

    public function withServers(?array $servers = null): self
    {
        $clone = clone $this;
        $clone->servers = $servers;

        return $clone;
    }

    public function withParameters(array $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;

        return $clone;
    }
}
