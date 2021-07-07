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

final class Parameter
{
    use ExtensionTrait;

    private $name;
    private $in;
    private $description;
    private $required;
    private $deprecated;
    private $allowEmptyValue;
    private $schema;
    private $explode;
    private $allowReserved;
    private $style;
    private $example;
    private $examples;
    private $content;

    public function __construct(string $name, string $in, string $description = '', bool $required = false, bool $deprecated = false, bool $allowEmptyValue = false, array $schema = [], string $style = null, bool $explode = false, bool $allowReserved = false, $example = null, \ArrayObject $examples = null, \ArrayObject $content = null)
    {
        $this->name = $name;
        $this->in = $in;
        $this->description = $description;
        $this->required = $required;
        $this->deprecated = $deprecated;
        $this->allowEmptyValue = $allowEmptyValue;
        $this->schema = $schema;
        $this->explode = $explode;
        $this->allowReserved = $allowReserved;
        $this->example = $example;
        $this->examples = $examples;
        $this->content = $content;
        $this->style = $style;

        if (null === $style) {
            if ('query' === $in || 'cookie' === $in) {
                $this->style = 'form';
            } elseif ('path' === $in || 'header' === $in) {
                $this->style = 'simple';
            }
        }
    }

    // TODO: string not ?string
    public function getName(): ?string
    {
        return $this->name;
    }

    // TODO: string not ?string
    public function getIn(): ?string
    {
        return $this->in;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function getDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function canAllowEmptyValue(): bool
    {
        return $this->allowEmptyValue;
    }

    public function getAllowEmptyValue(): bool
    {
        return $this->allowEmptyValue;
    }

    public function getSchema(): array
    {
        return $this->schema;
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

    public function getExample()
    {
        return $this->example;
    }

    public function getExamples(): ?\ArrayObject
    {
        return $this->examples;
    }

    public function getContent(): ?\ArrayObject
    {
        return $this->content;
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

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withRequired(bool $required): self
    {
        $clone = clone $this;
        $clone->required = $required;

        return $clone;
    }

    public function withDeprecated(bool $deprecated): self
    {
        $clone = clone $this;
        $clone->deprecated = $deprecated;

        return $clone;
    }

    public function withAllowEmptyValue(bool $allowEmptyValue): self
    {
        $clone = clone $this;
        $clone->allowEmptyValue = $allowEmptyValue;

        return $clone;
    }

    public function withSchema(array $schema): self
    {
        $clone = clone $this;
        $clone->schema = $schema;

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

    public function withContent(\ArrayObject $content): self
    {
        $clone = clone $this;
        $clone->content = $content;

        return $clone;
    }
}
