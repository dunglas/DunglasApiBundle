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
 *     @Attribute("maximumItemsPerPage", type="int"),
 *     @Attribute("paginationMaximumItemsPerPage", type="int"),
 *     @Attribute("paginationPartial", type="bool"),
 *     @Attribute("paginationViaCursor", type="array"),
 *     @Attribute("routePrefix", type="string"),
 *     @Attribute("security", type="string"),
 *     @Attribute("securityMessage", type="string"),
 *     @Attribute("securityPostDenormalize", type="string"),
 *     @Attribute("securityPostDenormalizeMessage", type="string"),
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
     * @see https://api-platform.com/docs/core/operations
     *
     * @var array
     */
    public $collectionOperations;

    /**
     * @var string
     */
    public $description;

    /**
     * @see https://api-platform.com/docs/core/graphql
     *
     * @var array
     */
    public $graphql;

    /**
     * @var string
     */
    public $iri;

    /**
     * @see https://api-platform.com/docs/core/operations
     *
     * @var array
     */
    public $itemOperations;

    /**
     * @var string
     */
    public $shortName;

    /**
     * @see https://api-platform.com/docs/core/subresources
     *
     * @var array
     */
    public $subresourceOperations;

    /**
     * @var bool
     */
    public $isInterface;

    /**
     * @var array
     */
    public $implements;

    /**
     * @see https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $cacheHeaders;

    /**
     * @see https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $denormalizationContext;

    /**
     * @see https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $deprecationReason;

    /**
     * @see https://api-platform.com/docs/core/elasticsearch/
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $elasticsearch;

    /**
     * @see https://api-platform.com/docs/core/performance/#fetch-partial
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $fetchPartial;

    /**
     * @see https://api-platform.com/docs/core/performance/#force-eager
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $forceEager;

    /**
     * @see https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $formats;

    /**
     * @see https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string[]
     */
    private $filters;

    /**
     * @see https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string[]
     */
    private $hydraContext;

    /**
     * @see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string|false
     */
    private $input;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     *
     * @deprecated - Use $paginationMaximumItemsPerPage instead
     */
    private $maximumItemsPerPage;

    /**
     * @see https://api-platform.com/docs/core/mercure
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     */
    private $mercure;

    /**
     * @see https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool|string
     */
    private $messenger;

    /**
     * @see https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $normalizationContext;

    /**
     * @see https://api-platform.com/docs/core/swagger/#using-the-openapi-and-swagger-contexts
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $openapiContext;

    /**
     * @see https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $order;

    /**
     * @see https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string|false
     */
    private $output;

    /**
     * @see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientEnabled;

    /**
     * @see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientItemsPerPage;

    /**
     * @see https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientPartial;

    /**
     * @see https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $paginationViaCursor;

    /**
     * @see https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationEnabled;

    /**
     * @see https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationFetchJoinCollection;

    /**
     * @see https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $paginationItemsPerPage;

    /**
     * @see https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $paginationMaximumItemsPerPage;

    /**
     * @see https://api-platform.com/docs/core/performance/#partial-pagination
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationPartial;

    /**
     * @see https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $routePrefix;

    /**
     * @see https://api-platform.com/docs/core/security
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $security;

    /**
     * @see https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $securityMessage;

    /**
     * @see https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $securityPostDenormalize;

    /**
     * @see https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $securityPostDenormalizeMessage;

    /**
     * @see https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $sunset;

    /**
     * @see https://api-platform.com/docs/core/swagger/#using-the-openapi-and-swagger-contexts
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $swaggerContext;

    /**
     * @see https://api-platform.com/docs/core/validation/#using-validation-groups
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     */
    private $validationGroups;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        $this->hydrateAttributes($values);
    }
}
