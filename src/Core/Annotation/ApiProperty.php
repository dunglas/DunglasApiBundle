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

namespace ApiPlatform\Core\Annotation;

use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * ApiProperty annotation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 * @Attributes(
 *     @Attribute("deprecationReason", type="string"),
 *     @Attribute("fetchable", type="bool"),
 *     @Attribute("fetchEager", type="bool"),
 *     @Attribute("openapiContext", type="array"),
 *     @Attribute("jsonldContext", type="array"),
 *     @Attribute("push", type="bool"),
 *     @Attribute("security", type="string"),
 *     @Attribute("securityPostDenormalize", type="string"),
 *     @Attribute("swaggerContext", type="array")
 * )
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ApiProperty
{
    use AttributesHydratorTrait;

    /**
     * @var array<string, array>
     */
    private static $deprecatedAttributes = [];

    /**
     * @var string
     */
    public $description;

    /**
     * @var bool
     */
    public $readable;

    /**
     * @var bool
     */
    public $writable;

    /**
     * @var bool
     */
    public $readableLink;

    /**
     * @var bool
     */
    public $writableLink;

    /**
     * @var bool
     */
    public $required;

    /**
     * @var string
     */
    public $iri;

    /**
     * @var bool
     */
    public $identifier;

    /**
     * @var string|int|float|bool|array|null
     */
    public $default;

    /**
     * @var string|int|float|bool|array|null
     */
    public $example;

    /**
     * @param string                           $description
     * @param bool                             $readable
     * @param bool                             $writable
     * @param bool                             $readableLink
     * @param bool                             $writableLink
     * @param bool                             $required
     * @param string                           $iri
     * @param bool                             $identifier
     * @param string|int|float|bool|array      $default
     * @param string|int|float|bool|array|null $example
     * @param string                           $deprecationReason
     * @param bool                             $fetchable
     * @param bool                             $fetchEager
     * @param array                            $jsonldContext
     * @param array                            $openapiContext
     * @param bool                             $push
     * @param string                           $security
     * @param array                            $swaggerContext
     * @param string                           $securityPostDenormalize
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $description = null,
        ?bool $readable = null,
        ?bool $writable = null,
        ?bool $readableLink = null,
        ?bool $writableLink = null,
        ?bool $required = null,
        ?string $iri = null,
        ?bool $identifier = null,
        $default = null,
        $example = null,

        // attributes
        ?array $attributes = null,
        ?string $deprecationReason = null,
        ?bool $fetchable = null,
        ?bool $fetchEager = null,
        ?array $jsonldContext = null,
        ?array $openapiContext = null,
        ?bool $push = null,
        ?string $security = null,
        ?array $swaggerContext = null,
        ?string $securityPostDenormalize = null
    ) {
        if (!\is_array($description)) { // @phpstan-ignore-line Doctrine annotations support
            [$publicProperties, $configurableAttributes] = self::getConfigMetadata();

            foreach ($publicProperties as $prop => $_) {
                $this->{$prop} = ${$prop};
            }

            $description = [];
            foreach ($configurableAttributes as $attribute => $_) {
                $description[$attribute] = ${$attribute};
            }
        }

        $this->hydrateAttributes($description);
    }
}
