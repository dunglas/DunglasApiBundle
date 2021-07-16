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

use ApiPlatform\Api\UrlGeneratorInterface;

class Operation
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_OPTIONS = 'OPTIONS';

    /**
     * @param string          $method
     * @param string          $uriTemplate
     * @param string          $shortName
     * @param string          $description
     * @param array           $types
     * @param array|string    $formats                        https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string    $inputFormats                   https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array|string    $outputFormats                  https://api-platform.com/docs/core/content-negotiation/#configuring-formats-for-a-specific-resource-or-operation
     * @param array           $identifiers
     * @param string          $routePrefix                    https://api-platform.com/docs/core/operations/#prefixing-all-routes-of-all-operations
     * @param string          $routeName
     * @param array           $defaults
     * @param array           $requirements
     * @param array           $options
     * @param bool            $stateless
     * @param string          $sunset                         https://api-platform.com/docs/core/deprecations/#setting-the-sunset-http-header-to-indicate-when-a-resource-or-an-operation-will-be-removed
     * @param string          $acceptPatch
     * @param string|int|null $status
     * @param string          $host
     * @param array           $schemes
     * @param string          $condition
     * @param string          $controller
     * @param string          $class
     * @param int             $urlGenerationStrategy
     * @param bool            $collection
     * @param string          $deprecationReason              https://api-platform.com/docs/core/deprecations/#deprecating-resource-classes-operations-and-properties
     * @param array           $cacheHeaders                   https://api-platform.com/docs/core/performance/#setting-custom-http-cache-headers
     * @param array           $normalizationContext           https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param array           $denormalizationContext         https://api-platform.com/docs/core/serialization/#using-serialization-groups
     * @param string[]        $hydraContext                   https://api-platform.com/docs/core/extending-jsonld-context/#hydra
     * @param array           $openapiContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param array           $swaggerContext                 https://api-platform.com/docs/core/openapi/#using-the-openapi-and-swagger-contexts
     * @param string[]        $filters                        https://api-platform.com/docs/core/filters/#doctrine-orm-and-mongodb-odm-filters
     * @param bool            $elasticsearch                  https://api-platform.com/docs/core/elasticsearch/
     * @param bool|array      $mercure                        https://api-platform.com/docs/core/mercure
     * @param bool            $messenger                      https://api-platform.com/docs/core/messenger/#dispatching-a-resource-through-the-message-bus
     * @param mixed           $input                          https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param mixed           $output                         https://api-platform.com/docs/core/dto/#specifying-an-input-or-an-output-data-representation
     * @param array           $order                          https://api-platform.com/docs/core/default-order/#overriding-default-order
     * @param bool            $fetchPartial                   https://api-platform.com/docs/core/performance/#fetch-partial
     * @param bool            $forceEager                     https://api-platform.com/docs/core/performance/#force-eager
     * @param bool            $paginationClientEnabled        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-1
     * @param bool            $paginationClientItemsPerPage   https://api-platform.com/docs/core/pagination/#for-a-specific-resource-3
     * @param bool            $paginationClientPartial        https://api-platform.com/docs/core/pagination/#for-a-specific-resource-6
     * @param array           $paginationViaCursor            https://api-platform.com/docs/core/pagination/#cursor-based-pagination
     * @param bool            $paginationEnabled              https://api-platform.com/docs/core/pagination/#for-a-specific-resource
     * @param bool            $paginationFetchJoinCollection  https://api-platform.com/docs/core/pagination/#controlling-the-behavior-of-the-doctrine-orm-paginator
     * @param int             $paginationItemsPerPage         https://api-platform.com/docs/core/pagination/#changing-the-number-of-items-per-page
     * @param int             $paginationMaximumItemsPerPage  https://api-platform.com/docs/core/pagination/#changing-maximum-items-per-page
     * @param bool            $paginationPartial              https://api-platform.com/docs/core/performance/#partial-pagination
     * @param string          $paginationType                 https://api-platform.com/docs/core/graphql/#using-the-page-based-pagination
     * @param string          $security                       https://api-platform.com/docs/core/security
     * @param string          $securityMessage                https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param string          $securityPostDenormalize        https://api-platform.com/docs/core/security/#executing-access-control-rules-after-denormalization
     * @param string          $securityPostDenormalizeMessage https://api-platform.com/docs/core/security/#configuring-the-access-control-error-message
     * @param bool            $compositeIdentifier
     * @param GraphQl|null    $graphQl
     * @param bool            $read                           https://api-platform.com/docs/core/events/#the-event-system
     * @param bool            $deserialize                    https://api-platform.com/docs/core/events/#the-event-system
     * @param bool            $validate                       https://api-platform.com/docs/core/events/#the-event-system
     * @param bool            $write                          https://api-platform.com/docs/core/events/#the-event-system
     * @param bool            $serialize                      https://api-platform.com/docs/core/events/#the-event-system
     */
    public function __construct(
        protected string $method = self::METHOD_GET,
        protected ?string $uriTemplate = null,
        protected ?string $shortName = null,
        protected ?string $description = null,
        protected array $types = [],
        protected mixed $formats = null,
        protected mixed $inputFormats = null,
        protected mixed $outputFormats = null,
        protected mixed $identifiers = [],
        protected string $routePrefix = '',
        protected ?string $routeName = null,
        protected array $defaults = [],
        protected array $requirements = [],
        protected array $options = [],
        protected ?bool $stateless = null,
        protected ?string $sunset = null,
        protected ?string $acceptPatch = null,
        protected ?int $status = null,
        protected string $host = '',
        protected array $schemes = [],
        protected string $condition = '',
        protected string $controller = 'api_platform.action.placeholder',
        protected ?string $class = null,
        protected int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH,
        protected bool $collection = false,
        protected ?string $deprecationReason = null,
        protected array $cacheHeaders = [],
        protected array $normalizationContext = [],
        protected array $denormalizationContext = [],
        protected array $hydraContext = [],
        protected array $openapiContext = [],
        protected array $swaggerContext = [],
        protected array $validationContext = [],
        protected array $filters = [],
        protected ?bool $elasticsearch = null,
        protected mixed $mercure = null,
        protected mixed $messenger = null,
        protected mixed $input = null,
        protected mixed $output = null,
        protected array $order = [],
        protected ?bool $fetchPartial = null,
        protected ?bool $forceEager = null,
        protected ?bool $paginationClientEnabled = null,
        protected ?bool $paginationClientItemsPerPage = null,
        protected ?bool $paginationClientPartial = null,
        protected array $paginationViaCursor = [],
        protected ?bool $paginationEnabled = null,
        protected ?bool $paginationFetchJoinCollection = null,
        protected ?int $paginationItemsPerPage = null,
        protected ?int $paginationMaximumItemsPerPage = null,
        protected ?bool $paginationPartial = null,
        protected ?string $paginationType = null,
        protected ?string $security = null,
        protected ?string $securityMessage = null,
        protected ?string $securityPostDenormalize = null,
        protected ?string $securityPostDenormalizeMessage = null,
        protected ?bool $compositeIdentifier = null,
        protected array $exceptionToStatus = [],
        protected ?bool $queryParameterValidationEnabled = null,
        protected ?GraphQl $graphQl = null,
        protected bool $read = true,
        protected bool $deserialize = true,
        protected bool $validate = true,
        protected bool $write = true,
        protected bool $serialize = true,
        // TODO: replace by queryParameterValidationEnabled?
        protected bool $queryParameterValidate = true,
        protected int $priority = 0,
        protected string $name = '',
        protected array $extraProperties = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        $self = clone $this;
        $self->name = $name;

        return $self;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): self
    {
        $self = clone $this;
        $self->method = $method;

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

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function withRouteName(?string $routeName = null): self
    {
        $self = clone $this;
        $self->routeName = $routeName;

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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function withStatus(?int $status = null): self
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

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function withCollection(bool $collection = false): self
    {
        $self = clone $this;
        $self->collection = $collection;

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

    public function getSwaggerContext(): array
    {
        return $this->swaggerContext;
    }

    public function withSwaggerContext(array $swaggerContext = []): self
    {
        $self = clone $this;
        $self->swaggerContext = $swaggerContext;

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

    public function canRead(): bool
    {
        return $this->read;
    }

    public function withRead(bool $read = true): self
    {
        $self = clone $this;
        $self->read = $read;

        return $self;
    }

    public function canDeserialize(): bool
    {
        return $this->deserialize;
    }

    public function withDeserialize(bool $deserialize = true): self
    {
        $self = clone $this;
        $self->deserialize = $deserialize;

        return $self;
    }

    public function canValidate(): bool
    {
        return $this->validate;
    }

    public function withValidate(bool $validate = true): self
    {
        $self = clone $this;
        $self->validate = $validate;

        return $self;
    }

    public function canWrite(): bool
    {
        return $this->write;
    }

    public function withWrite(bool $write = true): self
    {
        $self = clone $this;
        $self->write = $write;

        return $self;
    }

    public function canSerialize(): bool
    {
        return $this->serialize;
    }

    public function withSerialize(bool $serialize = true): self
    {
        $self = clone $this;
        $self->serialize = $serialize;

        return $self;
    }

    public function canQueryParameterValidate(): bool
    {
        return $this->validate;
    }

    public function withQueryParameterValidate(bool $validate = true): self
    {
        $self = clone $this;
        $self->validate = $validate;

        return $self;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function withPriority(int $priority = 0): self
    {
        $self = clone $this;
        $self->priority = $priority;

        return $self;
    }
}
