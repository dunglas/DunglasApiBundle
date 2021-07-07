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

namespace ApiPlatform\OpenApi\Factory;

use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\PaginationOptions;
use ApiPlatform\Core\JsonSchema\Schema;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceToResourceMetadataTrait;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\Model\ExternalDocumentation;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Options;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generates an Open API v3 specification.
 */
final class OpenApiFactory implements OpenApiFactoryInterface
{
    use FilterLocatorTrait;
    use ResourceToResourceMetadataTrait;

    public const BASE_URL = 'base_url';
    public const OPENAPI_DEFINITION_NAME = 'openapi_definition_name';

    private $resourceNameCollectionFactory;
    /**
     * @param ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
     */
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $operationPathResolver;
    private $formats;
    private $jsonSchemaFactory;
    private $jsonSchemaTypeFactory;
    private $openApiOptions;
    private $paginationOptions;
    private $identifiersExtractor;
    private $router;
    // TODO: remove this in 3.0
    private $decorated = null;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, SchemaFactoryInterface $jsonSchemaFactory, TypeFactoryInterface $jsonSchemaTypeFactory, OperationPathResolverInterface $operationPathResolver, ContainerInterface $filterLocator, IdentifiersExtractorInterface $identifiersExtractor = null, array $formats = [], Options $openApiOptions = null, PaginationOptions $paginationOptions = null, RouterInterface $router = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->jsonSchemaFactory = $jsonSchemaFactory;
        $this->jsonSchemaTypeFactory = $jsonSchemaTypeFactory;
        $this->formats = $formats;
        $this->setFilterLocator($filterLocator, true);
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->openApiOptions = $openApiOptions ?: new Options('API Platform');
        $this->paginationOptions = $paginationOptions ?: new PaginationOptions();
        $this->router = $router;

        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0, use %s instead.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class), \E_USER_DEPRECATED);
            $this->decorated = new LegacyOpenApiFactory($resourceNameCollectionFactory, $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, $jsonSchemaFactory, $jsonSchemaTypeFactory, $operationPathResolver, $filterLocator, $identifiersExtractor, $formats, $openApiOptions, $paginationOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $context = []): OpenApi
    {
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            return $this->decorated->__invoke($context);
        }

        $baseUrl = $context[self::BASE_URL] ?? '/';
        $contact = null === $this->openApiOptions->getContactUrl() || null === $this->openApiOptions->getContactEmail() ? null : new Model\Contact($this->openApiOptions->getContactName(), $this->openApiOptions->getContactUrl(), $this->openApiOptions->getContactEmail());
        $license = null === $this->openApiOptions->getLicenseName() ? null : new Model\License($this->openApiOptions->getLicenseName(), $this->openApiOptions->getLicenseUrl());
        $info = new Model\Info($this->openApiOptions->getTitle(), $this->openApiOptions->getVersion(), trim($this->openApiOptions->getDescription()), $this->openApiOptions->getTermsOfService(), $contact, $license);
        $servers = '/' === $baseUrl || '' === $baseUrl ? [new Model\Server('/')] : [new Model\Server($baseUrl)];
        $paths = new Model\Paths();
        $schemas = new \ArrayObject();

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resources = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($resources as $resource) {
                $this->collectPaths($resource, $resourceClass, $context, $paths, $schemas);
            }
        }

        $securitySchemes = $this->getSecuritySchemes();
        $securityRequirements = [];

        foreach (array_keys($securitySchemes) as $key) {
            $securityRequirements[] = [$key => []];
        }

        return new OpenApi(
            $info,
            $servers,
            $paths,
            new Model\Components(
                $schemas,
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject(),
                new \ArrayObject($securitySchemes)
            ),
            $securityRequirements
        );
    }

    private function collectPaths(Resource $resource, string $resourceClass, array $context, Model\Paths $paths, \ArrayObject $schemas): void
    {
        $links = [];
        $resourceShortName = $resource->getShortName();

        if (!$resource->getOperations()) {
            return;
        }

        $rootResourceClass = $resourceClass;

        foreach ($resource->getOperations() as $operationName => $operation) {
            // No path to return
            if (null === $operation->getUriTemplate() && null === $operation->getRouteName()) {
                continue;
            }

            $identifiers = $operation->getIdentifiers();
            $resourceClass = $operation->getClass() ?? $rootResourceClass;

            $path = $this->getPath($operation->getUriTemplate() ?? $this->router->getRouteCollection()->get($operation->getRouteName())->getPath());
            $method = $operation->getMethod();

            [$requestMimeTypes, $responseMimeTypes] = $this->getMimeTypes($operation);

            $operationId = $operation->getOpenapiContext()['operationId'] ?? $operationName;

            $linkedOperationId = $this->getLinkedOperationName($resource->getOperations());

            if ($path) {
                $pathItem = $paths->getPath($path) ?: new Model\PathItem();
            } else {
                $pathItem = new Model\PathItem();
            }

            $forceSchemaCollection = $operation->isCollection() ?? false;
            $schema = new Schema('openapi');
            $schema->setDefinitions($schemas);

            $operationOutputSchemas = [];

            foreach ($responseMimeTypes as $operationFormat) {
                $operationOutputSchema = $this->jsonSchemaFactory->buildSchema($resourceClass, $operationFormat, Schema::TYPE_OUTPUT, null, $operationName, $schema, null, $forceSchemaCollection);
                $operationOutputSchemas[$operationFormat] = $operationOutputSchema;
                $this->appendSchemaDefinitions($schemas, $operationOutputSchema->getDefinitions());
            }

            $parameters = [];
            $responses = [];

            if ($operation->getOpenapiContext()['parameters'] ?? false) {
                foreach ($operation->getOpenapiContext()['parameters'] as $parameter) {
                    $parameters[] = new Model\Parameter($parameter['name'], $parameter['in'], $parameter['description'] ?? '', $parameter['required'] ?? false, $parameter['deprecated'] ?? false, $parameter['allowEmptyValue'] ?? false, $parameter['schema'] ?? [], $parameter['style'] ?? null, $parameter['explode'] ?? false, $parameter['allowReserved '] ?? false, $parameter['example'] ?? null, isset($parameter['examples']) ? new \ArrayObject($parameter['examples']) : null, isset($parameter['content']) ? new \ArrayObject($parameter['content']) : null);
                }
            }

            // Set up parameters
            if ($identifiers) {
                foreach (array_keys($identifiers) as $parameterName) {
                    $parameter = new Model\Parameter($parameterName, 'path', $resource->getShortName().' identifier', true, false, false, ['type' => 'string']);
                    if ($this->hasParameter($parameter, $parameters)) {
                        continue;
                    }

                    $parameters[] = $parameter;
                }
            }

            if ($operation->isCollection()) {
                $resources = $this->resourceMetadataFactory->create($resourceClass);

                foreach (array_merge($this->getPaginationParameters($resource, $operationName), $this->getFiltersParameters($resources, $operationName, $resourceClass)) as $parameter) {
                    if ($this->hasParameter($parameter, $parameters)) {
                        continue;
                    }

                    $parameters[] = $parameter;
                }
                $links[$operationId] = $this->getLinks($resources, $operationName, $path);
            }

            // Create responses
            switch ($method) {
                case 'GET':
                    $successStatus = (string) $resource->getStatus() ?: 200;
                    $responseContent = $this->buildContent($responseMimeTypes, $operationOutputSchemas);
                    $responses[$successStatus] = new Model\Response(sprintf('%s resource', $resourceShortName), $responseContent);
                    break;
                case 'POST':
                    $responseLinks = new \ArrayObject(isset($links[$linkedOperationId]) ? [ucfirst($linkedOperationId) => $links[$linkedOperationId]] : []);
                    $responseContent = $this->buildContent($responseMimeTypes, $operationOutputSchemas);
                    $successStatus = (string) $resource->getStatus() ?: 201;
                    $responses[$successStatus] = new Model\Response(sprintf('%s resource created', $resourceShortName), $responseContent, null, $responseLinks);
                    $responses['400'] = new Model\Response('Invalid input');
                    $responses['422'] = new Model\Response('Unprocessable entity');
                    break;
                case 'PATCH':
                case 'PUT':
                    $responseLinks = new \ArrayObject(isset($links[$linkedOperationId]) ? [ucfirst($linkedOperationId) => $links[$linkedOperationId]] : []);
                    $successStatus = (string) $resource->getStatus() ?: 200;
                    $responseContent = $this->buildContent($responseMimeTypes, $operationOutputSchemas);
                    $responses[$successStatus] = new Model\Response(sprintf('%s resource updated', $resourceShortName), $responseContent, null, $responseLinks);
                    $responses['400'] = new Model\Response('Invalid input');
                    $responses['422'] = new Model\Response('Unprocessable entity');
                    break;
                case 'DELETE':
                    $successStatus = (string) $resource->getStatus() ?: 204;
                    $responses[$successStatus] = new Model\Response(sprintf('%s resource deleted', $resourceShortName));
                    break;
            }

            if (!$operation->isCollection()) {
                $responses['404'] = new Model\Response('Resource not found');
            }

            if (!$responses) {
                $responses['default'] = new Model\Response('Unexpected error');
            }

            if ($contextResponses = $operation->getOpenapiContext()['responses'] ?? false) {
                foreach ($contextResponses as $statusCode => $contextResponse) {
                    $responses[$statusCode] = new Model\Response($contextResponse['description'] ?? '', isset($contextResponse['content']) ? new \ArrayObject($contextResponse['content']) : null, isset($contextResponse['headers']) ? new \ArrayObject($contextResponse['headers']) : null, isset($contextResponse['links']) ? new \ArrayObject($contextResponse['links']) : null);
                }
            }

            $requestBody = null;
            if ($contextRequestBody = $operation->getOpenapiContext()['requestBody'] ?? false) {
                $requestBody = new Model\RequestBody($contextRequestBody['description'] ?? '', new \ArrayObject($contextRequestBody['content']), $contextRequestBody['required'] ?? false);
            } elseif ('PUT' === $method || 'POST' === $method || 'PATCH' === $method) {
                $operationInputSchemas = [];
                foreach ($requestMimeTypes as $operationFormat) {
                    $operationInputSchema = $this->jsonSchemaFactory->buildSchema($resourceClass, $operationFormat, Schema::TYPE_INPUT, null, $operationName, $schema, null, $forceSchemaCollection);
                    $operationInputSchemas[$operationFormat] = $operationInputSchema;
                    $this->appendSchemaDefinitions($schemas, $operationInputSchema->getDefinitions());
                }

                $requestBody = new Model\RequestBody(sprintf('The %s %s resource', 'POST' === $method ? 'new' : 'updated', $resourceShortName), $this->buildContent($requestMimeTypes, $operationInputSchemas), true);
            }

            $pathItem = $pathItem->{'with'.ucfirst($method)}(new Model\Operation(
                $operationId,
                $operation->getOpenapiContext()['tags'] ?? ([$operation->getShortName()] ?? [$resourceShortName]),
                $responses,
                $operation->getOpenapiContext()['summary'] ?? $this->getPathDescription($resourceShortName, $method),
                $operation->getOpenapiContext()['description'] ?? $this->getPathDescription($resourceShortName, $method),
                isset($operation->getOpenapiContext()['externalDocs']) ? new ExternalDocumentation($operation->getOpenapiContext()['externalDocs']['description'] ?? null, $operation->getOpenapiContext()['externalDocs']['url']) : null,
                $parameters,
                $requestBody,
                isset($operation->getOpenapiContext()['callbacks']) ? new \ArrayObject($operation->getOpenapiContext()['callbacks']) : null,
                $operation->getOpenapiContext()['deprecated'] ?? (bool) $operation->getDeprecationReason(),
                $operation->getOpenapiContext()['security'] ?? null,
                $operation->getOpenapiContext()['servers'] ?? null,
                array_filter($operation->getOpenapiContext() ?? [], static function ($item) {
                    return preg_match('/^x-.*$/i', $item);
                }, \ARRAY_FILTER_USE_KEY),
            ));

            $paths->addPath($path, $pathItem);
        }
    }

    private function buildContent(array $responseMimeTypes, array $operationSchemas): \ArrayObject
    {
        $content = new \ArrayObject();

        foreach ($responseMimeTypes as $mimeType => $format) {
            $content[$mimeType] = new Model\MediaType(new \ArrayObject($operationSchemas[$format]->getArrayCopy(false)));
        }

        return $content;
    }

    private function getMimeTypes(Operation $operation): array
    {
        $requestFormats = $operation->getInputFormats();
        $responseFormats = $operation->getOutputFormats();

        $requestMimeTypes = $this->flattenMimeTypes($requestFormats);
        $responseMimeTypes = $this->flattenMimeTypes($responseFormats);

        return [$requestMimeTypes, $responseMimeTypes];
    }

    private function flattenMimeTypes(array $responseFormats): array
    {
        $responseMimeTypes = [];
        foreach ($responseFormats as $responseFormat => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $responseMimeTypes[$mimeType] = $responseFormat;
            }
        }

        return $responseMimeTypes;
    }

    /**
     * Gets the path for an operation.
     *
     * If the path ends with the optional _format parameter, it is removed
     * as optional path parameters are not yet supported.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/issues/93
     */
    private function getPath(string $path): string
    {
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }

    private function getPathDescription(string $resourceShortName, string $method): string
    {
        switch ($method) {
            case 'GET':
                $pathSummary = 'Retrieves a %s resource.';
                break;
            case 'POST':
                $pathSummary = 'Creates a %s resource.';
                break;
            case 'PATCH':
                $pathSummary = 'Updates the %s resource.';
                break;
            case 'PUT':
                $pathSummary = 'Replaces the %s resource.';
                break;
            case 'DELETE':
                $pathSummary = 'Removes the %s resource.';
                break;
            default:
                return $resourceShortName;
        }

        return sprintf($pathSummary, $resourceShortName);
    }

    /**
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md#linkObject.
     */
    private function getLinks(ResourceMetadataCollection $resources, string $operationName, string $path): Model\Link
    {
        $parameters = [];

        foreach ($resources as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                if ('GET' !== $operation->getMethod() || $operation->isCollection()) {
                    continue;
                }
                foreach ($resource->getIdentifiers() as $parameterName => [$class, $propertyName]) {
                    $parameters[$parameterName] = sprintf('$response.body#/%s', $propertyName);
                }
            }
        }

        return new Model\Link(
            $operationName, // operationName
            new \ArrayObject($parameters),
            null,
            1 === \count($parameters) ? sprintf('The `%1$s` value returned in the response can be used as the `%1$s` parameter in `GET %2$s`.', key($parameters), $path) : sprintf('The values returned in the response can be used in `GET %s`.', $path)
        );
    }

    /**
     * Gets parameters corresponding to enabled filters.
     */
    private function getFiltersParameters(ResourceMetadataCollection $resource, string $operationName, string $resourceClass): array
    {
        $parameters = [];

        $resourceFilters = $resource->getOperation($operationName)->getFilters();
        foreach ($resourceFilters ?? [] as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($resourceClass) as $name => $data) {
                $schema = $data['schema'] ?? (\in_array($data['type'], Type::$builtinTypes, true) ? $this->jsonSchemaTypeFactory->getType(new Type($data['type'], false, null, $data['is_collection'] ?? false)) : ['type' => 'string']);

                $parameters[] = new Model\Parameter(
                    $name,
                    'query',
                    $data['description'] ?? '',
                    $data['required'] ?? false,
                    $data['openapi']['deprecated'] ?? false,
                    $data['openapi']['allowEmptyValue'] ?? true,
                    $schema,
                    'array' === $schema['type'] && \in_array(
                        $data['type'],
                        [Type::BUILTIN_TYPE_ARRAY, Type::BUILTIN_TYPE_OBJECT],
                        true
                    ) ? 'deepObject' : 'form',
                    $data['openapi']['explode'] ?? ('array' === $schema['type']),
                    $data['openapi']['allowReserved'] ?? false,
                    $data['openapi']['example'] ?? null,
                    isset($data['openapi']['examples']
                    ) ? new \ArrayObject($data['openapi']['examples']) : null
                );
            }
        }

        return $parameters;
    }

    private function getPaginationParameters(Resource $resource, string $operationName): array
    {
        if (!$this->paginationOptions->isPaginationEnabled()) {
            return [];
        }

        $parameters = [];

        if ($resource->getPaginationEnabled()) {
            $parameters[] = new Model\Parameter($this->paginationOptions->getPaginationPageParameterName(), 'query', 'The collection page number', false, false, true, ['type' => 'integer', 'default' => 1]);

            if ($resource->getPaginationClientItemsPerPage()) {
                $schema = [
                    'type' => 'integer',
                    'default' => $resource->getPaginationItemsPerPage(),
                    'minimum' => 0,
                ];

                if (null !== $maxItemsPerPage = $resource->getPaginationMaximumItemsPerPage()) {
                    $schema['maximum'] = $maxItemsPerPage;
                }

                $parameters[] = new Model\Parameter($this->paginationOptions->getItemsPerPageParameterName(), 'query', 'The number of items per page', false, false, true, $schema);
            }
        }

        if ($resource->getPaginationClientEnabled()) {
            $parameters[] = new Model\Parameter($this->paginationOptions->getPaginationClientEnabledParameterName(), 'query', 'Enable or disable pagination', false, false, true, ['type' => 'boolean']);
        }

        return $parameters;
    }

    private function getOauthSecurityScheme(): Model\SecurityScheme
    {
        $oauthFlow = new Model\OAuthFlow($this->openApiOptions->getOAuthAuthorizationUrl(), $this->openApiOptions->getOAuthTokenUrl(), $this->openApiOptions->getOAuthRefreshUrl(), new \ArrayObject($this->openApiOptions->getOAuthScopes()));
        $description = sprintf(
            'OAuth 2.0 %s Grant',
            strtolower(preg_replace('/[A-Z]/', ' \\0', lcfirst($this->openApiOptions->getOAuthFlow())))
        );
        $implicit = $password = $clientCredentials = $authorizationCode = null;

        switch ($this->openApiOptions->getOAuthFlow()) {
            case 'implicit':
                $implicit = $oauthFlow;
                break;
            case 'password':
                $password = $oauthFlow;
                break;
            case 'application':
            case 'clientCredentials':
                $clientCredentials = $oauthFlow;
                break;
            case 'accessCode':
            case 'authorizationCode':
                $authorizationCode = $oauthFlow;
                break;
            default:
                throw new \LogicException('OAuth flow must be one of: implicit, password, clientCredentials, authorizationCode');
        }

        return new Model\SecurityScheme($this->openApiOptions->getOAuthType(), $description, null, null, null, null, new Model\OAuthFlows($implicit, $password, $clientCredentials, $authorizationCode), null);
    }

    private function getSecuritySchemes(): array
    {
        $securitySchemes = [];

        if ($this->openApiOptions->getOAuthEnabled()) {
            $securitySchemes['oauth'] = $this->getOauthSecurityScheme();
        }

        foreach ($this->openApiOptions->getApiKeys() as $key => $apiKey) {
            $description = sprintf('Value for the %s %s parameter.', $apiKey['name'], $apiKey['type']);
            $securitySchemes[$key] = new Model\SecurityScheme('apiKey', $description, $apiKey['name'], $apiKey['type']);
        }

        return $securitySchemes;
    }

    private function appendSchemaDefinitions(\ArrayObject $schemas, \ArrayObject $definitions): void
    {
        foreach ($definitions as $key => $value) {
            $schemas[$key] = $value;
        }
    }

    /**
     * @param Model\Parameter[] $parameters
     */
    private function hasParameter(Model\Parameter $parameter, array $parameters): bool
    {
        foreach ($parameters as $existingParameter) {
            if ($existingParameter->getName() === $parameter->getName() && $existingParameter->getIn() === $parameter->getIn()) {
                return true;
            }
        }

        return false;
    }

    private function getLinkedOperationName($operations): ?string
    {
        foreach ($operations as $operationName => $operation) {
            if ('GET' === $operation->getMethod() && $operation->getIdentifiers()) {
                return $operationName;
            }
        }

        return null;
    }
}
