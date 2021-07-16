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
 *     @Attribute("compositeIdentifier", type="bool"),
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
 *     @Attribute("stateless", type="bool"),
 *     @Attribute("subresourceOperations", type="array"),
 *     @Attribute("sunset", type="string"),
 *     @Attribute("swaggerContext", type="array"),
 *     @Attribute("urlGenerationStrategy", type="int"),
 *     @Attribute("validationGroups", type="mixed"),
 *     @Attribute("exceptionToStatus", type="array"),
 *     @Attribute("queryParameterValidationEnabled", type="bool")
 * )
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ApiResource
{
    use AttributesHydratorTrait;

    /**
     * @var array<string, array>
     */
    private static $deprecatedAttributes = [
        'accessControl' => ['security', '2.5'],
        'accessControlMessage' => ['securityMessage', '2.5'],
        'maximumItemsPerPage' => ['paginationMaximumItemsPerPage', '2.6'],
    ];

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
     * @param string       $description
     * @param array        $collectionOperations            https://api-platform.com/docs/core/operations
     * @param array        $graphql                         https://api-platform.com/docs/core/graphql
     * @param array        $itemOperations                  https://api-platform.com/docs/core/operations
     * @param array        $subresourceOperations           https://api-platform.com/docs/core/subresources
     * @param array        $cacheHeaders                    https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @param array        $denormalizationContext          https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param string       $deprecationReason               https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param bool         $elasticsearch                   https://api-platform.com/docs/core/elasticsearch/
     * @param bool         $fetchPartial                    https://api-platform.com/docs/core/performance/#fetch-partial
     * @param bool         $forceEager                      https://api-platform.com/docs/core/performance/#force-eager
     * @param array        $formats                         https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param string[]     $filters                         https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @param string[]     $hydraContext                    https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @param string|false $input                           https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param bool|array   $mercure                         https://api-platform.com/docs/core/mercure
     * @param bool         $messenger                       https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @param array        $normalizationContext            https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param array        $openapiContext                  https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array        $order                           https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @param string|false $output                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param bool         $paginationClientEnabled         https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @param bool         $paginationClientItemsPerPage    https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @param bool         $paginationClientPartial         https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @param array        $paginationViaCursor             https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @param bool         $paginationEnabled               https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @param bool         $paginationFetchJoinCollection   https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @param int          $paginationItemsPerPage          https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @param int          $paginationMaximumItemsPerPage   https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @param bool         $paginationPartial               https://api-platform.com/docs/core/performance/#partial-pagination
     * @param string       $routePrefix                     https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @param string       $security                        https://api-platform.com/docs/core/security
     * @param string       $securityMessage                 https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string       $securityPostDenormalize         https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string       $securityPostDenormalizeMessage  https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param bool         $stateless
     * @param string       $sunset                          https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @param array        $swaggerContext                  https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array        $validationGroups                https://api-platform.com/docs/core/validation/#using-validation-groups
     * @param int          $urlGenerationStrategy
     * @param array        $exceptionToStatus               https://api-platform.com/docs/core/errors/#fine-grained-configuration
     * @param bool         $queryParameterValidationEnabled
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $description = null,
        ?array $collectionOperations = null,
        ?array $graphql = null,
        ?string $iri = null,
        ?array $itemOperations = null,
        ?string $shortName = null,
        ?array $subresourceOperations = null,

        // attributes
        ?array $attributes = null,
        ?array $cacheHeaders = null,
        ?array $denormalizationContext = null,
        ?string $deprecationReason = null,
        ?bool $elasticsearch = null,
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        ?array $formats = null,
        ?array $filters = null,
        ?array $hydraContext = null,
        $input = null,
        $mercure = null,
        $messenger = null,
        ?array $normalizationContext = null,
        ?array $openapiContext = null,
        ?array $order = null,
        $output = null,
        ?bool $paginationClientEnabled = null,
        ?bool $paginationClientItemsPerPage = null,
        ?bool $paginationClientPartial = null,
        ?array $paginationViaCursor = null,
        ?bool $paginationEnabled = null,
        ?bool $paginationFetchJoinCollection = null,
        ?int $paginationItemsPerPage = null,
        ?int $paginationMaximumItemsPerPage = null,
        ?bool $paginationPartial = null,
        ?string $routePrefix = null,
        ?string $security = null,
        ?string $securityMessage = null,
        ?string $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        ?bool $stateless = null,
        ?string $sunset = null,
        ?array $swaggerContext = null,
        ?array $validationGroups = null,
        ?int $urlGenerationStrategy = null,
        ?bool $compositeIdentifier = null,
        ?array $exceptionToStatus = null,
        ?bool $queryParameterValidationEnabled = null
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

        $this->hydrateAttributes($description ?? []);
    }
}
