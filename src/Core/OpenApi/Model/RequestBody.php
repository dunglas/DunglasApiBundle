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

final class RequestBody
{
    use ExtensionTrait;

    private $description;
    private $content;
    private $required;

    public function __construct(string $description = '', \ArrayObject $content = null, bool $required = false)
    {
        $this->description = $description;
        $this->content = $content;
        $this->required = $required;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContent(): \ArrayObject
    {
        return $this->content;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withContent(\ArrayObject $content): self
    {
        $clone = clone $this;
        $clone->content = $content;

        return $clone;
    }

    public function withRequired(bool $required): self
    {
        $clone = clone $this;
        $clone->required = $required;

        return $clone;
    }
}
