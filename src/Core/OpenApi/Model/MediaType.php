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

final class MediaType
{
    use ExtensionTrait;

    private $schema;
    private $example;
    private $examples;
    private $encoding;

    public function __construct(\ArrayObject $schema = null, $example = null, \ArrayObject $examples = null, Encoding $encoding = null)
    {
        $this->schema = $schema;
        $this->example = $example;
        $this->examples = $examples;
        $this->encoding = $encoding;
    }

    public function getSchema(): ?\ArrayObject
    {
        return $this->schema;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function getExamples(): ?\ArrayObject
    {
        return $this->examples;
    }

    public function getEncoding(): ?Encoding
    {
        return $this->encoding;
    }

    public function withSchema(\ArrayObject $schema): self
    {
        $clone = clone $this;
        $clone->schema = $schema;

        return $clone;
    }

    public function withExample($example): self
    {
        $clone = clone $this;
        $clone->example = $example;

        return $clone;
    }

    public function withExamples(\ArrayObject $examples): self
    {
        $clone = clone $this;
        $clone->examples = $examples;

        return $clone;
    }

    public function withEncoding(Encoding $encoding): self
    {
        $clone = clone $this;
        $clone->encoding = $encoding;

        return $clone;
    }
}
