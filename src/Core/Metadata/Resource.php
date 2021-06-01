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

/**
 * Resource metadata attribute.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Resource
{
    use AttributeTrait;

    /**
     * @param string            $uriTemplate
     * @param string            $description
     * @param array             $types
     * @param string            $shortName
     * @param array             $cacheHeaders                   https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @param array             $denormalizationContext         https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param string            $deprecationReason              https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param bool              $elasticsearch                  https://api-platform.com/docs/core/elasticsearch/
     * @param bool              $fetchPartial                   https://api-platform.com/docs/core/performance/#fetch-partial
     * @param bool              $forceEager                     https://api-platform.com/docs/core/performance/#force-eager
     * @param array|string|null $formats                        https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string|null $inputFormats                   https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string|null $outputFormats                  https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param string[]          $filters                        https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @param string[]          $hydraContext                   https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @param mixed             $input                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param bool|array        $mercure                        https://api-platform.com/docs/core/mercure
     * @param bool              $messenger                      https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @param array             $normalizationContext           https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param array             $openapiContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array             $order                          https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @param mixed             $output                         https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param bool              $paginationClientEnabled        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @param bool              $paginationClientItemsPerPage   https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @param bool              $paginationClientPartial        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @param array             $paginationViaCursor            https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @param bool              $paginationEnabled              https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @param bool              $paginationFetchJoinCollection  https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @param int               $paginationItemsPerPage         https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @param int               $paginationMaximumItemsPerPage  https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @param bool              $paginationPartial              https://api-platform.com/docs/core/performance/#partial-pagination
     * @param string            $routePrefix                    https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @param string            $security                       https://api-platform.com/docs/core/security
     * @param string            $securityMessage                https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string            $securityPostDenormalize        https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string            $securityPostDenormalizeMessage https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param bool              $stateless
     * @param string            $sunset                         https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @param array             $swaggerContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array             $validationGroups               https://api-platform.com/docs/core/validation/#using-validation-groups
     * @param int               $urlGenerationStrategy
     * @param bool              $compositeIdentifier
     * @param array             $identifiers
     * @param array             $graphQl
     */
    public function __construct(
        public ?string $uriTemplate = null,
        public ?string $shortName = null,
        public ?string $description = null,
        public ?string $class = null,
        public ?array $types = null,
        public ?array $operations = null,

        public ?array $cacheHeaders = null,
        public ?array $denormalizationContext = null,
        public ?string $deprecationReason = null,
        public ?bool $elasticsearch = null,
        public ?bool $fetchPartial = null,
        public ?bool $forceEager = null,
        public mixed $formats = null,
        public mixed $inputFormats = null,
        public mixed $outputFormats = null,
        public ?array $filters = null,
        public ?array $hydraContext = null,
        public mixed $input = null,
        public $mercure = null,
        public $messenger = null,
        public ?array $normalizationContext = null,
        public ?array $openapiContext = null,
        public ?array $order = null,
        public mixed $output = null,
        public ?bool $paginationClientEnabled = null,
        public ?bool $paginationClientItemsPerPage = null,
        public ?bool $paginationClientPartial = null,
        public ?array $paginationViaCursor = null,
        public ?bool $paginationEnabled = null,
        public ?bool $paginationFetchJoinCollection = null,
        public ?int $paginationItemsPerPage = null,
        public ?int $paginationMaximumItemsPerPage = null,
        public ?bool $paginationPartial = null,
        public ?string $routePrefix = null,
        public ?string $security = null,
        public ?string $securityMessage = null,
        public ?string $securityPostDenormalize = null,
        public ?string $securityPostDenormalizeMessage = null,
        public ?bool $stateless = null,
        public ?string $sunset = null,
        public ?array $swaggerContext = null,
        public ?array $validationGroups = null,
        public ?int $urlGenerationStrategy = null,
        public ?bool $compositeIdentifier = null,
        public ?array $identifiers = null,
        public ?array $graphQl = null,
        ...$extraProperties
    ) {
        $this->extraProperties = $extraProperties;
    }
}
