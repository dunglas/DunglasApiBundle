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
 * ApiResource annotation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @Annotation
 * @Target({"CLASS"})
 * @Attributes(
 *     @Attribute("accessControl", type="string"),
 *     @Attribute("accessControlMessage", type="string"),
 *     @Attribute("attributes", type="array"),
 *     @Attribute("cacheHeaders", type="array"),
 *     @Attribute("collectionOperations", type="array"),
 *     @Attribute("denormalizationContext", type="array"),
 *     @Attribute("deprecationReason", type="string"),
 *     @Attribute("description", type="string"),
 *     @Attribute("doctrineCache", type="array"),
 *     @Attribute("elasticsearch", type="bool"),
 *     @Attribute("fetchPartial", type="bool"),
 *     @Attribute("forceEager", type="bool"),
 *     @Attribute("formats", type="array"),
 *     @Attribute("filters", type="string[]"),
 *     @Attribute("graphql", type="array"),
 *     @Attribute("hydraContext", type="array"),
 *     @Attribute("input", type="mixed"),
 *     @Attribute("iri", type="string"),
 *     @Attribute("itemOperations", type="array"),
 *     @Attribute("maximumItemsPerPage", type="int"),
 *     @Attribute("mercure", type="mixed"),
 *     @Attribute("messenger", type="mixed"),
 *     @Attribute("normalizationContext", type="array"),
 *     @Attribute("openapiContext", type="array"),
 *     @Attribute("order", type="array"),
 *     @Attribute("output", type="mixed"),
 *     @Attribute("paginationClientEnabled", type="bool"),
 *     @Attribute("paginationClientItemsPerPage", type="bool"),
 *     @Attribute("paginationClientPartial", type="bool"),
 *     @Attribute("paginationEnabled", type="bool"),
 *     @Attribute("paginationFetchJoinCollection", type="bool"),
 *     @Attribute("paginationItemsPerPage", type="int"),
 *     @Attribute("paginationPartial", type="bool"),
 *     @Attribute("paginationViaCursor", type="array"),
 *     @Attribute("routePrefix", type="string"),
 *     @Attribute("shortName", type="string"),
 *     @Attribute("subresourceOperations", type="array"),
 *     @Attribute("sunset", type="string"),
 *     @Attribute("swaggerContext", type="array"),
 *     @Attribute("validationGroups", type="mixed")
 * )
 */
final class ApiResource
{
    use AttributesHydratorTrait;

    /**
     * @var string
     */
    public $shortName;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $iri;

    /**
     * @var array
     */
    public $itemOperations;

    /**
     * @var array
     */
    public $collectionOperations;

    /**
     * @var array
     */
    public $subresourceOperations;

    /**
     * @var array
     */
    public $graphql;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $accessControl;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $accessControlMessage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $cacheHeaders;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $denormalizationContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $deprecationReason;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $doctrineCache;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $elasticsearch;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $fetchPartial;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $forceEager;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $formats;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string[]
     */
    private $filters;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string[]
     */
    private $hydraContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $maximumItemsPerPage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var mixed
     */
    private $mercure;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool|string
     */
    private $messenger;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $normalizationContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $order;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientEnabled;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientItemsPerPage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientPartial;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationEnabled;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationFetchJoinCollection;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $paginationItemsPerPage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $paginationPartial;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $routePrefix;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $swaggerContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var mixed
     */
    private $validationGroups;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $sunset;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string|false
     */
    private $input;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string|false
     */
    private $output;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $openapiContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $paginationViaCursor;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        $this->hydrateAttributes($values);
    }
}
