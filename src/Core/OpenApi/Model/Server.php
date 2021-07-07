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

final class Server
{
    use ExtensionTrait;

    private $url;
    private $description;
    private $variables;

    public function __construct(string $url, string $description = '', \ArrayObject $variables = null)
    {
        $this->url = $url;
        $this->description = $description;
        $this->variables = $variables;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVariables(): ?\ArrayObject
    {
        return $this->variables;
    }

    public function withUrl(string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withVariables(\ArrayObject $variables): self
    {
        $clone = clone $this;
        $clone->variables = $variables;

        return $clone;
    }
}
