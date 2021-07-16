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
class ApiResource
{
    /**
     * @param iterable     $operations
     * @param string       $uriTemplate
     * @param string       $shortName
     * @param string       $description
     * @param array        $types
     * @param array|string $formats                        https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string $inputFormats                   https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string $outputFormats                  https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param string       $routePrefix                    https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @param array        $defaults
     * @param array        $requirements
     * @param array        $options
     * @param bool         $stateless
     * @param string       $sunset                         https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @param string       $acceptPatch
     * @param string       $status
     * @param string       $host
     * @param array        $schemes
     * @param string       $condition
     * @param string       $controller
     * @param string       $class
     * @param int          $urlGenerationStrategy
     * @param string       $deprecationReason              https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param array        $cacheHeaders                   https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @param array        $normalizationContext           https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param array        $denormalizationContext         https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param string[]     $hydraContext                   https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @param array        $openapiContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array        $validationContext              https://api-platform.com/docs/core/validation/#using-validation-groups
     * @param string[]     $filters                        https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @param bool         $elasticsearch                  https://api-platform.com/docs/core/elasticsearch/
     * @param bool|array   $mercure                        https://api-platform.com/docs/core/mercure
     * @param bool         $messenger                      https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @param mixed        $input                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param mixed        $output                         https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param array        $order                          https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @param bool         $fetchPartial                   https://api-platform.com/docs/core/performance/#fetch-partial
     * @param bool         $forceEager                     https://api-platform.com/docs/core/performance/#force-eager
     * @param bool         $paginationClientEnabled        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @param bool         $paginationClientItemsPerPage   https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @param bool         $paginationClientPartial        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @param array        $paginationViaCursor            https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @param bool         $paginationEnabled              https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @param bool         $paginationFetchJoinCollection  https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @param int          $paginationItemsPerPage         https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @param int          $paginationMaximumItemsPerPage  https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @param bool         $paginationPartial              https://api-platform.com/docs/core/performance/#partial-pagination
     * @param string       $paginationType                 https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination
     * @param string       $security                       https://api-platform.com/docs/core/security
     * @param string       $securityMessage                https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string       $securityPostDenormalize        https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string       $securityPostDenormalizeMessage https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param bool         $compositeIdentifier
     * @param array        $exceptionToStatus
     * @param array        $graphQl
     */
    public function __construct(
        private ?string $uriTemplate = null,
        private ?string $shortName = null,
        private ?string $description = null,
        private array $types = [],
        private iterable $operations = [],
        private mixed $formats = null,
        private mixed $inputFormats = null,
        private mixed $outputFormats = null,
        private mixed $identifiers = [],
        private ?string $routePrefix = '',
        private array $defaults = [],
        private array $requirements = [],
        private array $options = [],
        private ?bool $stateless = null,
        private ?string $sunset = null,
        private ?string $acceptPatch = null,
        private mixed $status = null,
        private string $host = '',
        private array $schemes = [],
        private string $condition = '',
        private string $controller = 'api_platform.action.placeholder',
        private ?string $class = null,
        private ?int $urlGenerationStrategy = null,
        private ?string $deprecationReason = null,
        private array $cacheHeaders = [],
        private ?array $normalizationContext = [],
        private ?array $denormalizationContext = [],
        private ?array $hydraContext = [],
        private ?array $openapiContext = [],
        private array $validationContext = [],
        private array $filters = [],
        private ?bool $elasticsearch = null,
        private mixed $mercure = null,
        private mixed $messenger = null,
        private mixed $input = null,
        private mixed $output = null,
        private ?array $order = [],
        private ?bool $fetchPartial = null,
        private ?bool $forceEager = null,
        private ?bool $paginationClientEnabled = null,
        private ?bool $paginationClientItemsPerPage = null,
        private ?bool $paginationClientPartial = null,
        private ?array $paginationViaCursor = [],
        private ?bool $paginationEnabled = null,
        private ?bool $paginationFetchJoinCollection = null,
        private ?int $paginationItemsPerPage = null,
        private ?int $paginationMaximumItemsPerPage = null,
        private ?bool $paginationPartial = null,
        private ?string $paginationType = null,
        private ?string $security = null,
        private ?string $securityMessage = null,
        private ?string $securityPostDenormalize = null,
        private ?string $securityPostDenormalizeMessage = null,
        private ?bool $compositeIdentifier = null,
        private array $exceptionToStatus = [],
        private ?bool $queryParameterValidationEnabled = null,
        private ?GraphQl $graphQl = null,
        private array $extraProperties = []
    ) {
        $this->operations = new Operations($operations);
    }

    /**
     * @return array<string, Operation>
     */
    public function getOperations(): Operations
    {
        return $this->operations;
    }

    public function withOperations(array $operations = []): self
    {
        $self = clone $this;
        $self->operations = new Operations($operations);

        return $self;
    }

    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    public function withUriTemplate(?string $uriTemplate = null)
    {
        $self = clone $this;
        $self->uriTemplate = $uriTemplate;

        return $self;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function withShortName(?string $shortName = null)
    {
        $self = clone $this;
        $self->shortName = $shortName;

        return $self;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(?string $description = null)
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function withTypes(array $types = [])
    {
        $self = clone $this;
        $self->types = $types;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getFormats(): mixed
    {
        return $this->formats;
    }

    public function withFormats(mixed $formats = null): self
    {
        $self = clone $this;
        $self->formats = $formats;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getInputFormats(): mixed
    {
        return $this->inputFormats;
    }

    public function withInputFormats(mixed $inputFormats = null): self
    {
        $self = clone $this;
        $self->inputFormats = $inputFormats;

        return $self;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getOutputFormats(): mixed
    {
        return $this->outputFormats;
    }

    public function withOutputFormats(mixed $outputFormats = null): self
    {
        $self = clone $this;
        $self->outputFormats = $outputFormats;

        return $self;
    }

    public function getIdentifiers(): mixed
    {
        return $this->identifiers;
    }

    public function withIdentifiers(mixed $identifiers = []): self
    {
        $self = clone $this;
        $self->identifiers = $identifiers;

        return $self;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function withRoutePrefix(string $routePrefix = ''): self
    {
        $self = clone $this;
        $self->routePrefix = $routePrefix;

        return $self;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function withDefaults(array $defaults = []): self
    {
        $self = clone $this;
        $self->defaults = $defaults;

        return $self;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function withRequirements(array $requirements = []): self
    {
        $self = clone $this;
        $self->requirements = $requirements;

        return $self;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function withOptions(array $options = []): self
    {
        $self = clone $this;
        $self->options = $options;

        return $self;
    }

    public function getStateless(): ?bool
    {
        return $this->stateless;
    }

    public function withStateless(?bool $stateless = null): self
    {
        $self = clone $this;
        $self->stateless = $stateless;

        return $self;
    }

    public function getSunset(): ?string
    {
        return $this->sunset;
    }

    public function withSunset(?string $sunset = null): self
    {
        $self = clone $this;
        $self->sunset = $sunset;

        return $self;
    }

    public function getAcceptPatch(): ?string
    {
        return $this->acceptPatch;
    }

    public function withAcceptPatch(?string $acceptPatch = null): self
    {
        $self = clone $this;
        $self->acceptPatch = $acceptPatch;

        return $self;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function withStatus(?string $status = null): self
    {
        $self = clone $this;
        $self->status = $status;

        return $self;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function withHost(string $host = ''): self
    {
        $self = clone $this;
        $self->host = $host;

        return $self;
    }

    public function getSchemes(): array
    {
        return $this->schemes;
    }

    public function withSchemes(array $schemes = []): self
    {
        $self = clone $this;
        $self->schemes = $schemes;

        return $self;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function withCondition(string $condition = ''): self
    {
        $self = clone $this;
        $self->condition = $condition;

        return $self;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function withController(string $controller = ''): self
    {
        $self = clone $this;
        $self->controller = $controller;

        return $self;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function withClass(?string $class = null): self
    {
        $self = clone $this;
        $self->class = $class;

        return $self;
    }

    public function getUrlGenerationStrategy(): ?int
    {
        return $this->urlGenerationStrategy;
    }

    public function withUrlGenerationStrategy(?int $urlGenerationStrategy = null): self
    {
        $self = clone $this;
        $self->urlGenerationStrategy = $urlGenerationStrategy;

        return $self;
    }

    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    public function withDeprecationReason(?string $deprecationReason = null): self
    {
        $self = clone $this;
        $self->deprecationReason = $deprecationReason;

        return $self;
    }

    public function getCacheHeaders(): array
    {
        return $this->cacheHeaders;
    }

    public function withCacheHeaders(array $cacheHeaders = []): self
    {
        $self = clone $this;
        $self->cacheHeaders = $cacheHeaders;

        return $self;
    }

    public function getNormalizationContext(): array
    {
        return $this->normalizationContext;
    }

    public function withNormalizationContext(array $normalizationContext = []): self
    {
        $self = clone $this;
        $self->normalizationContext = $normalizationContext;

        return $self;
    }

    public function getDenormalizationContext(): array
    {
        return $this->denormalizationContext;
    }

    public function withDenormalizationContext(array $denormalizationContext = []): self
    {
        $self = clone $this;
        $self->denormalizationContext = $denormalizationContext;

        return $self;
    }

    /**
     * @return string[]
     */
    public function getHydraContext(): array
    {
        return $this->hydraContext;
    }

    public function withHydraContext(array $hydraContext = []): self
    {
        $self = clone $this;
        $self->hydraContext = $hydraContext;

        return $self;
    }

    public function getOpenapiContext(): array
    {
        return $this->openapiContext;
    }

    public function withOpenapiContext(array $openapiContext = []): self
    {
        $self = clone $this;
        $self->openapiContext = $openapiContext;

        return $self;
    }

    public function getValidationContext(): array
    {
        return $this->validationContext;
    }

    public function withValidationContext(array $validationContext = []): self
    {
        $self = clone $this;
        $self->validationContext = $validationContext;

        return $self;
    }

    /**
     * @return string[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function withFilters(array $filters = []): self
    {
        $self = clone $this;
        $self->filters = $filters;

        return $self;
    }

    public function getElasticsearch(): ?bool
    {
        return $this->elasticsearch;
    }

    public function withElasticsearch(?bool $elasticsearch = null): self
    {
        $self = clone $this;
        $self->elasticsearch = $elasticsearch;

        return $self;
    }

    /**
     * @return array|bool|mixed|null
     */
    public function getMercure(): mixed
    {
        return $this->mercure;
    }

    public function withMercure(mixed $mercure = null): self
    {
        $self = clone $this;
        $self->mercure = $mercure;

        return $self;
    }

    /**
     * @return bool|mixed|null
     */
    public function getMessenger(): mixed
    {
        return $this->messenger;
    }

    public function withMessenger(mixed $messenger = null): self
    {
        $self = clone $this;
        $self->messenger = $messenger;

        return $self;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function withInput(mixed $input = null): self
    {
        $self = clone $this;
        $self->input = $input;

        return $self;
    }

    public function getOutput(): mixed
    {
        return $this->output;
    }

    public function withOutput(mixed $output = null): self
    {
        $self = clone $this;
        $self->output = $output;

        return $self;
    }

    public function getOrder(): array
    {
        return $this->order;
    }

    public function withOrder(array $order = []): self
    {
        $self = clone $this;
        $self->order = $order;

        return $self;
    }

    public function getFetchPartial(): ?bool
    {
        return $this->fetchPartial;
    }

    public function withFetchPartial(?bool $fetchPartial = null): self
    {
        $self = clone $this;
        $self->fetchPartial = $fetchPartial;

        return $self;
    }

    public function getForceEager(): ?bool
    {
        return $this->forceEager;
    }

    public function withForceEager(?bool $forceEager = null): self
    {
        $self = clone $this;
        $self->forceEager = $forceEager;

        return $self;
    }

    public function getPaginationClientEnabled(): ?bool
    {
        return $this->paginationClientEnabled;
    }

    public function withPaginationClientEnabled(?bool $paginationClientEnabled = null): self
    {
        $self = clone $this;
        $self->paginationClientEnabled = $paginationClientEnabled;

        return $self;
    }

    public function getPaginationClientItemsPerPage(): ?bool
    {
        return $this->paginationClientItemsPerPage;
    }

    public function withPaginationClientItemsPerPage(?bool $paginationClientItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationClientItemsPerPage = $paginationClientItemsPerPage;

        return $self;
    }

    public function getPaginationClientPartial(): ?bool
    {
        return $this->paginationClientPartial;
    }

    public function withPaginationClientPartial(?bool $paginationClientPartial = null): self
    {
        $self = clone $this;
        $self->paginationClientPartial = $paginationClientPartial;

        return $self;
    }

    public function getPaginationViaCursor(): array
    {
        return $this->paginationViaCursor;
    }

    public function withPaginationViaCursor(array $paginationViaCursor = []): self
    {
        $self = clone $this;
        $self->paginationViaCursor = $paginationViaCursor;

        return $self;
    }

    public function getPaginationEnabled(): ?bool
    {
        return $this->paginationEnabled;
    }

    public function withPaginationEnabled(?bool $paginationEnabled = null): self
    {
        $self = clone $this;
        $self->paginationEnabled = $paginationEnabled;

        return $self;
    }

    public function getPaginationFetchJoinCollection(): ?bool
    {
        return $this->paginationFetchJoinCollection;
    }

    public function withPaginationFetchJoinCollection(?bool $paginationFetchJoinCollection = null): self
    {
        $self = clone $this;
        $self->paginationFetchJoinCollection = $paginationFetchJoinCollection;

        return $self;
    }

    public function getPaginationItemsPerPage(): ?int
    {
        return $this->paginationItemsPerPage;
    }

    public function withPaginationItemsPerPage(?int $paginationItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationItemsPerPage = $paginationItemsPerPage;

        return $self;
    }

    public function getPaginationMaximumItemsPerPage(): ?int
    {
        return $this->paginationMaximumItemsPerPage;
    }

    public function withPaginationMaximumItemsPerPage(?int $paginationMaximumItemsPerPage = null): self
    {
        $self = clone $this;
        $self->paginationMaximumItemsPerPage = $paginationMaximumItemsPerPage;

        return $self;
    }

    public function getPaginationPartial(): ?bool
    {
        return $this->paginationPartial;
    }

    public function withPaginationPartial(?bool $paginationPartial = null): self
    {
        $self = clone $this;
        $self->paginationPartial = $paginationPartial;

        return $self;
    }

    public function getPaginationType(): ?string
    {
        return $this->paginationType;
    }

    public function withPaginationType(?string $paginationType = null): self
    {
        $self = clone $this;
        $self->paginationType = $paginationType;

        return $self;
    }

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    public function withSecurity(?string $security = null): self
    {
        $self = clone $this;
        $self->security = $security;

        return $self;
    }

    public function getSecurityMessage(): ?string
    {
        return $this->securityMessage;
    }

    public function withSecurityMessage(?string $securityMessage = null): self
    {
        $self = clone $this;
        $self->securityMessage = $securityMessage;

        return $self;
    }

    public function getSecurityPostDenormalize(): ?string
    {
        return $this->securityPostDenormalize;
    }

    public function withSecurityPostDenormalize(?string $securityPostDenormalize = null): self
    {
        $self = clone $this;
        $self->securityPostDenormalize = $securityPostDenormalize;

        return $self;
    }

    public function getSecurityPostDenormalizeMessage(): ?string
    {
        return $this->securityPostDenormalizeMessage;
    }

    public function withSecurityPostDenormalizeMessage(?string $securityPostDenormalizeMessage = null): self
    {
        $self = clone $this;
        $self->securityPostDenormalizeMessage = $securityPostDenormalizeMessage;

        return $self;
    }

    public function getCompositeIdentifier(): ?bool
    {
        return $this->compositeIdentifier;
    }

    public function withCompositeIdentifier(?bool $compositeIdentifier = null): self
    {
        $self = clone $this;
        $self->compositeIdentifier = $compositeIdentifier;

        return $self;
    }

    public function getExceptionToStatus(): ?array
    {
        return $this->exceptionToStatus;
    }

    public function withExceptionToStatus(?array $exceptionToStatus = []): self
    {
        $self = clone $this;
        $self->exceptionToStatus = $exceptionToStatus;

        return $self;
    }

    public function getQueryParameterValidationEnabled(): ?bool
    {
        return $this->queryParameterValidationEnabled;
    }

    public function withQueryParameterValidationEnabled(?bool $queryParameterValidationEnabled = null): self
    {
        $self = clone $this;
        $self->queryParameterValidationEnabled = $queryParameterValidationEnabled;

        return $self;
    }

    public function getGraphQl(): ?GraphQl
    {
        return $this->graphQl;
    }

    public function withGraphQl(?GraphQl $graphQl = null): self
    {
        $self = clone $this;
        $self->graphQl = $graphQl;

        return $self;
    }

    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    public function withExtraProperties(array $extraProperties = []): self
    {
        $self = clone $this;
        $self->extraProperties = $extraProperties;

        return $self;
    }
}
