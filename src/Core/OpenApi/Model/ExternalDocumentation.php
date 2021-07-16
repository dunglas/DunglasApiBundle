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

final class ExternalDocumentation
{
    use ExtensionTrait;

    private $description;
    private $url;

    public function __construct(string $description = '', string $url = '')
    {
        $this->description = $description;
        $this->url = $url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withUrl(string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }
}
