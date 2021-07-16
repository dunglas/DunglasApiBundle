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

final class Encoding
{
    use ExtensionTrait;

    private $contentType;
    private $headers;
    private $style;
    private $explode;
    private $allowReserved;

    public function __construct(string $contentType = '', \ArrayObject $headers = null, string $style = '', bool $explode = false, bool $allowReserved = false)
    {
        $this->contentType = $contentType;
        $this->headers = $headers;
        $this->style = $style;
        $this->explode = $explode;
        $this->allowReserved = $allowReserved;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getHeaders(): ?\ArrayObject
    {
        return $this->headers;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function canExplode(): bool
    {
        return $this->explode;
    }

    public function getExplode(): bool
    {
        return $this->explode;
    }

    public function canAllowReserved(): bool
    {
        return $this->allowReserved;
    }

    public function getAllowReserved(): bool
    {
        return $this->allowReserved;
    }

    public function withContentType(string $contentType): self
    {
        $clone = clone $this;
        $clone->contentType = $contentType;

        return $clone;
    }

    public function withHeaders(?\ArrayObject $headers): self
    {
        $clone = clone $this;
        $clone->headers = $headers;

        return $clone;
    }

    public function withStyle(string $style): self
    {
        $clone = clone $this;
        $clone->style = $style;

        return $clone;
    }

    public function withExplode(bool $explode): self
    {
        $clone = clone $this;
        $clone->explode = $explode;

        return $clone;
    }

    public function withAllowReserved(bool $allowReserved): self
    {
        $clone = clone $this;
        $clone->allowReserved = $allowReserved;

        return $clone;
    }
}
