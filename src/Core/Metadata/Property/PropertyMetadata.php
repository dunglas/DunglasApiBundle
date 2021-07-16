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

namespace ApiPlatform\Core\Metadata\Property;

use Symfony\Component\PropertyInfo\Type;

/**
 * Property metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PropertyMetadata
{
    /**
     * @deprecated since 2.7, to be removed in 3.0, renamed as builtinTypes
     */
    private $type;
    private $description;
    private $readable;
    private $writable;
    private $readableLink;
    private $writableLink;
    private $required;
    /**
     * @deprecated since 2.7, to be removed in 3.0, renamed as types
     */
    private $iri;
    private $identifier;
    /**
     * @deprecated since 2.6, to be removed in 3.0
     */
    private $childInherited;
    private $attributes;
    private $subresource;
    private $initializable;
    /**
     * @var null
     */
    private $default;
    /**
     * @var null
     */
    private $example;
    private $schema;

    /**
     * @var Type[]
     */
    private $builtinTypes;
    /**
     * @var string[]
     */
    private $types;

    public function __construct(Type $type = null, string $description = null, bool $readable = null, bool $writable = null, bool $readableLink = null, bool $writableLink = null, bool $required = null, bool $identifier = null, string $iri = null, $childInherited = null, array $attributes = null, SubresourceMetadata $subresource = null, bool $initializable = null, $default = null, $example = null, array $schema = null, array $builtinTypes = null, array $types = null)
    {
        $this->type = $type;
        $this->description = $description;
        $this->readable = $readable;
        $this->writable = $writable;
        $this->readableLink = $readableLink;
        $this->writableLink = $writableLink;
        $this->required = $required;
        $this->identifier = $identifier;
        $this->iri = $iri;
        if (null !== $childInherited) {
            @trigger_error(sprintf('Providing a non-null value for the 10th argument ($childInherited) of the "%s" constructor is deprecated since 2.6 and will not be supported in 3.0.', __CLASS__), \E_USER_DEPRECATED);
        }
        $this->childInherited = $childInherited;
        $this->attributes = $attributes;
        $this->subresource = $subresource;
        $this->initializable = $initializable;
        $this->default = $default;
        $this->example = $example;
        $this->schema = $schema;
        $this->builtinTypes = $builtinTypes;
        $this->types = $types;
    }

    /**
     * Gets type.
     *
     * @deprecated since 2.7, to be removed in 3.0, renamed as getBuiltinTypes
     */
    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * Returns a new instance with the given type.
     *
     * @deprecated since 2.7, to be removed in 3.0, renamed as withBuiltinTypes
     */
    public function withType(Type $type): self
    {
        $metadata = clone $this;
        $metadata->type = $type;

        return $metadata;
    }

    /**
     * Gets description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns a new instance with the given description.
     */
    public function withDescription(string $description): self
    {
        $metadata = clone $this;
        $metadata->description = $description;

        return $metadata;
    }

    /**
     * Is readable?
     */
    public function isReadable(): ?bool
    {
        return $this->readable;
    }

    /**
     * Returns a new instance of Metadata with the given readable flag.
     */
    public function withReadable(bool $readable): self
    {
        $metadata = clone $this;
        $metadata->readable = $readable;

        return $metadata;
    }

    /**
     * Is writable?
     */
    public function isWritable(): ?bool
    {
        return $this->writable;
    }

    /**
     * Returns a new instance with the given writable flag.
     */
    public function withWritable(bool $writable): self
    {
        $metadata = clone $this;
        $metadata->writable = $writable;

        return $metadata;
    }

    /**
     * Is required?
     */
    public function isRequired(): ?bool
    {
        if (true === $this->required && false === $this->writable) {
            return false;
        }

        return $this->required;
    }

    /**
     * Returns a new instance with the given required flag.
     */
    public function withRequired(bool $required): self
    {
        $metadata = clone $this;
        $metadata->required = $required;

        return $metadata;
    }

    /**
     * Should an IRI or an object be provided in write context?
     */
    public function isWritableLink(): ?bool
    {
        return $this->writableLink;
    }

    /**
     * Returns a new instance with the given writable link flag.
     */
    public function withWritableLink(bool $writableLink): self
    {
        $metadata = clone $this;
        $metadata->writableLink = $writableLink;

        return $metadata;
    }

    /**
     * Is an IRI or an object generated in read context?
     */
    public function isReadableLink(): ?bool
    {
        return $this->readableLink;
    }

    /**
     * Returns a new instance with the given readable link flag.
     */
    public function withReadableLink(bool $readableLink): self
    {
        $metadata = clone $this;
        $metadata->readableLink = $readableLink;

        return $metadata;
    }

    /**
     * Gets IRI of this property.
     */
    public function getIri(): ?string
    {
        return $this->iri;
    }

    /**
     * Returns a new instance with the given IRI.
     */
    public function withIri(string $iri = null): self
    {
        $metadata = clone $this;
        $metadata->iri = $iri;

        return $metadata;
    }

    /**
     * Is this attribute an identifier?
     */
    public function isIdentifier(): ?bool
    {
        return $this->identifier;
    }

    /**
     * Returns a new instance with the given identifier flag.
     */
    public function withIdentifier(bool $identifier): self
    {
        $metadata = clone $this;
        $metadata->identifier = $identifier;

        return $metadata;
    }

    /**
     * Gets attributes.
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * Gets an attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getAttribute(string $key, $defaultValue = null)
    {
        return $this->attributes[$key] ?? $defaultValue;
    }

    /**
     * Returns a new instance with the given attribute.
     */
    public function withAttributes(array $attributes): self
    {
        $metadata = clone $this;
        $metadata->attributes = $attributes;

        return $metadata;
    }

    /**
     * @deprecated since 2.6, to be removed in 3.0
     */
    public function getChildInherited(): ?string
    {
        return $this->childInherited;
    }

    /**
     * @deprecated since 2.6, to be removed in 3.0
     */
    public function hasChildInherited(): bool
    {
        return null !== $this->childInherited;
    }

    /**
     * @deprecated since 2.4, to be removed in 3.0
     */
    public function isChildInherited(): ?string
    {
        @trigger_error(sprintf('"%s::%s" is deprecated since 2.4 and will be removed in 3.0.', __CLASS__, __METHOD__), \E_USER_DEPRECATED);

        return $this->getChildInherited();
    }

    /**
     * @deprecated since 2.6, to be removed in 3.0
     */
    public function withChildInherited(string $childInherited): self
    {
        @trigger_error(sprintf('"%s::%s" is deprecated since 2.6 and will be removed in 3.0.', __CLASS__, __METHOD__), \E_USER_DEPRECATED);

        $metadata = clone $this;
        $metadata->childInherited = $childInherited;

        return $metadata;
    }

    /**
     * Represents whether the property has a subresource.
     */
    public function hasSubresource(): bool
    {
        return null !== $this->subresource;
    }

    /**
     * Gets the subresource metadata.
     */
    public function getSubresource(): ?SubresourceMetadata
    {
        return $this->subresource;
    }

    /**
     * Returns a new instance with the given subresource.
     *
     * @param SubresourceMetadata $subresource
     */
    public function withSubresource(SubresourceMetadata $subresource = null): self
    {
        $metadata = clone $this;
        $metadata->subresource = $subresource;

        return $metadata;
    }

    /**
     * Is initializable?
     */
    public function isInitializable(): ?bool
    {
        return $this->initializable;
    }

    /**
     * Returns a new instance with the given initializable flag.
     */
    public function withInitializable(bool $initializable): self
    {
        $metadata = clone $this;
        $metadata->initializable = $initializable;

        return $metadata;
    }

    /**
     * Returns the default value of the property or NULL if the property doesn't have a default value.
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Returns a new instance with the given default value for the property.
     */
    public function withDefault($default): self
    {
        $metadata = clone $this;
        $metadata->default = $default;

        return $metadata;
    }

    /**
     * Returns an example of the value of the property.
     */
    public function getExample()
    {
        return $this->example;
    }

    /**
     * Returns a new instance with the given example.
     */
    public function withExample($example): self
    {
        $metadata = clone $this;
        $metadata->example = $example;

        return $metadata;
    }

    /**
     * @return array
     */
    public function getSchema(): ?array
    {
        return $this->schema;
    }

    /**
     * Returns a new instance with the given schema.
     */
    public function withSchema(array $schema = null): self
    {
        $metadata = clone $this;
        $metadata->schema = $schema;

        return $metadata;
    }

    /**
     * @return Type[]
     */
    public function getBuiltinTypes(): ?array
    {
        return $this->builtinTypes;
    }

    /**
     * Returns a new instance with the given builtinTypes.
     */
    public function withBuiltinTypes(array $builtinTypes = null): self
    {
        $metadata = clone $this;
        $metadata->builtinTypes = $builtinTypes;

        return $metadata;
    }

    /**
     * @return string[]
     */
    public function getTypes(): ?array
    {
        return $this->types;
    }

    /**
     * Returns a new instance with the given Types.
     */
    public function withTypes(array $types = null): self
    {
        $metadata = clone $this;
        $metadata->types = $types;

        return $metadata;
    }
}
