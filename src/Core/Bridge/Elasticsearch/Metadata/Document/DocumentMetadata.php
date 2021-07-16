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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document;

/**
 * Document metadata.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-fields.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class DocumentMetadata
{
    public const DEFAULT_TYPE = '_doc';

    private $index;
    private $type;

    public function __construct(?string $index = null, string $type = self::DEFAULT_TYPE)
    {
        $this->index = $index;
        $this->type = $type;
    }

    /**
     * Gets a new instance with the given index.
     */
    public function withIndex(string $index): self
    {
        $metadata = clone $this;
        $metadata->index = $index;

        return $metadata;
    }

    /**
     * Gets the document index.
     */
    public function getIndex(): ?string
    {
        return $this->index;
    }

    /**
     * Gets a new instance with the given type.
     */
    public function withType(string $type): self
    {
        $metadata = clone $this;
        $metadata->type = $type;

        return $metadata;
    }

    /**
     * Gets the document type.
     */
    public function getType(): string
    {
        return $this->type;
    }
}
