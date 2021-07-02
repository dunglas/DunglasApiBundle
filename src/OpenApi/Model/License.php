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

final class License
{
    use ExtensionTrait;

    private $name;
    private $url;
    private $identifier;

    public function __construct(string $name, string $url = null, string $identifier = null)
    {
        $this->name = $name;
        $this->url = $url;
        $this->identifier = $identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function withUrl(?string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }

    public function withIdentifier(?string $identifier): self
    {
        $clone = clone $this;
        $clone->identifier = $identifier;

        return $clone;
    }
}
