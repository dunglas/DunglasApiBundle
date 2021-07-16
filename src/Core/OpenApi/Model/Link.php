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

final class Link
{
    use ExtensionTrait;

    private $operationId;
    private $parameters;
    private $requestBody;
    private $description;
    private $server;

    public function __construct(string $operationId, \ArrayObject $parameters = null, $requestBody = null, string $description = '', Server $server = null)
    {
        $this->operationId = $operationId;
        $this->parameters = $parameters;
        $this->requestBody = $requestBody;
        $this->description = $description;
        $this->server = $server;
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function getParameters(): \ArrayObject
    {
        return $this->parameters;
    }

    public function getRequestBody()
    {
        return $this->requestBody;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function withOperationId(string $operationId): self
    {
        $clone = clone $this;
        $clone->operationId = $operationId;

        return $clone;
    }

    public function withParameters(\ArrayObject $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;

        return $clone;
    }

    public function withRequestBody($requestBody): self
    {
        $clone = clone $this;
        $clone->requestBody = $requestBody;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withServer(Server $server): self
    {
        $clone = clone $this;
        $clone->server = $server;

        return $clone;
    }
}
