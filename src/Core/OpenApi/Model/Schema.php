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

use ApiPlatform\Core\JsonSchema\Schema as JsonSchema;

final class Schema extends \ArrayObject
{
    use ExtensionTrait;

    private $nullable;
    private $discriminator;
    private $readOnly;
    private $writeOnly;
    private $xml;
    private $externalDocs;
    private $example;
    private $deprecated;
    private $schema;

    public function __construct(bool $nullable = false, $discriminator = null, bool $readOnly = false, bool $writeOnly = false, string $xml = null, $externalDocs = null, $example = null, bool $deprecated = false)
    {
        $this->nullable = $nullable;
        $this->discriminator = $discriminator;
        $this->readOnly = $readOnly;
        $this->writeOnly = $writeOnly;
        $this->xml = $xml;
        $this->externalDocs = $externalDocs;
        $this->example = $example;
        $this->deprecated = $deprecated;
        $this->schema = new JsonSchema();

        parent::__construct([]);
    }

    public function setDefinitions(array $definitions)
    {
        $this->schema->setDefinitions(new \ArrayObject($definitions));
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayCopy(): array
    {
        $schema = parent::getArrayCopy();
        unset($schema['schema']);

        return $schema;
    }

    public function getDefinitions(): \ArrayObject
    {
        return new \ArrayObject(array_merge($this->schema->getArrayCopy(true), $this->getArrayCopy()));
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function getDiscriminator()
    {
        return $this->discriminator;
    }

    public function getReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function getWriteOnly(): bool
    {
        return $this->writeOnly;
    }

    public function getXml(): string
    {
        return $this->xml;
    }

    public function getExternalDocs()
    {
        return $this->externalDocs;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function getDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function withNullable(bool $nullable): self
    {
        $clone = clone $this;
        $clone->nullable = $nullable;

        return $clone;
    }

    public function withDiscriminator($discriminator): self
    {
        $clone = clone $this;
        $clone->discriminator = $discriminator;

        return $clone;
    }

    public function withReadOnly(bool $readOnly): self
    {
        $clone = clone $this;
        $clone->readOnly = $readOnly;

        return $clone;
    }

    public function withWriteOnly(bool $writeOnly): self
    {
        $clone = clone $this;
        $clone->writeOnly = $writeOnly;

        return $clone;
    }

    public function withXml(string $xml): self
    {
        $clone = clone $this;
        $clone->xml = $xml;

        return $clone;
    }

    public function withExternalDocs($externalDocs): self
    {
        $clone = clone $this;
        $clone->externalDocs = $externalDocs;

        return $clone;
    }

    public function withExample($example): self
    {
        $clone = clone $this;
        $clone->example = $example;

        return $clone;
    }

    public function withDeprecated(bool $deprecated): self
    {
        $clone = clone $this;
        $clone->deprecated = $deprecated;

        return $clone;
    }
}
