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

namespace ApiPlatform\Metadata;

use Symfony\Component\PropertyInfo\Type;

/**
 * Property metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ApiProperty
{
    /**
     * @param string[] $types
     * @param Type[]   $builtinTypes
     */
    public function __construct(
        private array $types = [],
        private array $builtinTypes = [],
        private string $description = '',
        private bool $readable = true,
        private bool $writable = true,
        private bool $readableLink = true,
        private bool $writableLink = true,
        private bool $required = false,
        private bool $identifier = false,
        private bool $initializable = false,
        private mixed $default = null,
        private mixed $example = null,
        private array $schema = [],
        private ?string $deprecationReason = null,
        private ?bool $fetchable = null,
        private ?bool $fetchEager = null,
        private ?array $jsonldContext = null,
        private ?array $openapiContext = null,
        private ?bool $push = null,
        private ?string $security = null,
        private ?string $securityPostDenormalize = null,
        private array $extraProperties = []
    ) {
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return Type[]
     */
    public function getBuiltinTypes(): array
    {
        return $this->builtinTypes;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function isReadableLink(): bool
    {
        return $this->readableLink;
    }

    public function isWritableLink(): bool
    {
        return $this->writableLink;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isIdentifier(): bool
    {
        return $this->identifier;
    }

    public function isInitializable(): bool
    {
        return $this->initializable;
    }

    /**
     * @return mixed|null
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return mixed|null
     */
    public function getExample(): mixed
    {
        return $this->example;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @return string|null
     */
    public function getDeprecationReason(): string
    {
        return $this->deprecationReason;
    }

    /**
     * @return bool|null
     */
    public function getFetchable(): bool
    {
        return $this->fetchable;
    }

    /**
     * @return bool|null
     */
    public function getFetchEager(): bool
    {
        return $this->fetchEager;
    }

    /**
     * @return array|null
     */
    public function getJsonldContext(): array
    {
        return $this->jsonldContext;
    }

    /**
     * @return array|null
     */
    public function getOpenapiContext(): array
    {
        return $this->openapiContext;
    }

    /**
     * @return bool|null
     */
    public function getPush(): bool
    {
        return $this->push;
    }

    /**
     * @return string|null
     */
    public function getSecurity(): string
    {
        return $this->security;
    }

    /**
     * @return string|null
     */
    public function getSecurityPostDenormalize(): string
    {
        return $this->securityPostDenormalize;
    }

    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }
}
