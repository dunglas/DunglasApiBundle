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

namespace ApiPlatform\Core\Swagger\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\JsonSchema\Schema;
use ApiPlatform\Core\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Core\JsonSchema\TypeFactory;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceToResourceMetadataTrait;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Generates an OpenAPI specification (formerly known as Swagger). OpenAPI v2 and v3 are supported.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class DocumentationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use FilterLocatorTrait;
    use ResourceToResourceMetadataTrait;

    public const FORMAT = 'json';
    public const BASE_URL = 'base_url';
    public const SPEC_VERSION = 'spec_version';
    public const OPENAPI_VERSION = '3.0.2';
    public const SWAGGER_DEFINITION_NAME = 'swagger_definition_name';
    public const SWAGGER_VERSION = '2.0';

    /**
     * @deprecated
     */
    public const ATTRIBUTE_NAME = 'swagger_context';

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $operationMethodResolver;
    private $operationPathResolver;
    private $oauthEnabled;
    private $oauthType;
    private $oauthFlow;
    private $oauthTokenUrl;
    private $oauthAuthorizationUrl;
    private $oauthScopes;
    private $apiKeys;
    private $subresourceOperationFactory;
    private $paginationEnabled;
    private $paginationPageParameterName;
    private $clientItemsPerPage;
    private $itemsPerPageParameterName;
    private $paginationClientEnabled;
    private $paginationClientEnabledParameterName;
    private $formats;
    private $formatsProvider;

    /**
     * @var SchemaFactoryInterface
     */
    private $jsonSchemaFactory;
    /**
     * @var TypeFactoryInterface
     */
    private $jsonSchemaTypeFactory;
    private $defaultContext = [
        self::BASE_URL => '/',
        ApiGatewayNormalizer::API_GATEWAY => false,
    ];

    private $identifiersExtractor;

    private $openApiNormalizer;

    /**
     * @param SchemaFactoryInterface|ResourceClassResolverInterface|null $jsonSchemaFactory
     * @param ContainerInterface|FilterCollection|null                   $filterLocator
     * @param array|OperationAwareFormatsProviderInterface               $formats
     * @param mixed|null                                                 $jsonSchemaTypeFactory
     * @param int[]                                                      $swaggerVersions
     */
    public function __construct($resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, $jsonSchemaFactory = null, $jsonSchemaTypeFactory = null, OperationPathResolverInterface $operationPathResolver = null, UrlGeneratorInterface $urlGenerator = null, $filterLocator = null, NameConverterInterface $nameConverter = null, bool $oauthEnabled = false, string $oauthType = '', string $oauthFlow = '', string $oauthTokenUrl = '', string $oauthAuthorizationUrl = '', array $oauthScopes = [], array $apiKeys = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null, bool $paginationEnabled = true, string $paginationPageParameterName = 'page', bool $clientItemsPerPage = false, string $itemsPerPageParameterName = 'itemsPerPage', $formats = [], bool $paginationClientEnabled = false, string $paginationClientEnabledParameterName = 'pagination', array $defaultContext = [], array $swaggerVersions = [2, 3], IdentifiersExtractorInterface $identifiersExtractor = null, NormalizerInterface $openApiNormalizer = null)
    {
        if ($jsonSchemaTypeFactory instanceof OperationMethodResolverInterface) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.5 and will be removed in 3.0.', OperationMethodResolverInterface::class, __METHOD__), \E_USER_DEPRECATED);

            $this->operationMethodResolver = $jsonSchemaTypeFactory;
            $this->jsonSchemaTypeFactory = new TypeFactory();
        } else {
            $this->jsonSchemaTypeFactory = $jsonSchemaTypeFactory ?? new TypeFactory();
        }

        if ($jsonSchemaFactory instanceof ResourceClassResolverInterface) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.5 and will be removed in 3.0.', ResourceClassResolverInterface::class, __METHOD__), \E_USER_DEPRECATED);
        }

        if (null === $jsonSchemaFactory || $jsonSchemaFactory instanceof ResourceClassResolverInterface) {
            $jsonSchemaFactory = new SchemaFactory($this->jsonSchemaTypeFactory, $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, $nameConverter);
            $this->jsonSchemaTypeFactory->setSchemaFactory($jsonSchemaFactory);
        }
        $this->jsonSchemaFactory = $jsonSchemaFactory;

        if ($nameConverter) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.5 and will be removed in 3.0.', NameConverterInterface::class, __METHOD__), \E_USER_DEPRECATED);
        }

        if ($urlGenerator) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.1 and will be removed in 3.0.', UrlGeneratorInterface::class, __METHOD__), \E_USER_DEPRECATED);
        }

        if ($formats instanceof FormatsProviderInterface) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.5 and will be removed in 3.0, pass an array instead.', FormatsProviderInterface::class, __METHOD__), \E_USER_DEPRECATED);

            $this->formatsProvider = $formats;
        } else {
            $this->formats = $formats;
        }

        $this->setFilterLocator($filterLocator, true);

        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0, use %s instead.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class), \E_USER_DEPRECATED);
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->paginationEnabled = $paginationEnabled;
        $this->paginationPageParameterName = $paginationPageParameterName;
        $this->apiKeys = $apiKeys;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
        $this->paginationClientEnabled = $paginationClientEnabled;
        $this->paginationClientEnabledParameterName = $paginationClientEnabledParameterName;

        $this->defaultContext[self::SPEC_VERSION] = $swaggerVersions[0] ?? 2;

        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
        $this->identifiersExtractor = $identifiersExtractor;
        $this->openApiNormalizer = $openApiNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($object instanceof OpenApi) {
            @trigger_error('Using the swagger DocumentationNormalizer is deprecated in favor of decorating the OpenApiFactory, use the "openapi.backward_compatibility_layer" configuration to change this behavior.', \E_USER_DEPRECATED);

            return $this->openApiNormalizer->normalize($object, $format, $context);
        }

        $v3 = 3 === ($context['spec_version'] ?? $this->defaultContext['spec_version']) && !($context['api_gateway'] ?? $this->defaultContext['api_gateway']);

        $definitions = new \ArrayObject();
        $paths = new \ArrayObject();
        $links = new \ArrayObject();

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                $resourceMetadata = $this->transformResourceToResourceMetadata($resourceMetadata[0]);
            }

            if ($this->identifiersExtractor) {
                $identifiers = [];
                if ($resourceMetadata->getItemOperations()) {
                    $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass);
                }

                $resourceMetadata = $resourceMetadata->withAttributes(($resourceMetadata->getAttributes() ?: []) + ['identifiers' => $identifiers]);
            }
            $resourceShortName = $resourceMetadata->getShortName();

            // Items needs to be parsed first to be able to reference the lines from the collection operation
            $this->addPaths($v3, $paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, OperationType::ITEM, $links);
            $this->addPaths($v3, $paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, OperationType::COLLECTION, $links);

            if (null === $this->subresourceOperationFactory) {
                continue;
            }

            foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $subresourceOperation) {
                $method = $resourceMetadata->getTypedOperationAttribute(OperationType::SUBRESOURCE, $subresourceOperation['operation_name'], 'method', 'GET');
                $paths[$this->getPath($subresourceOperation['shortNames'][0], $subresourceOperation['route_name'], $subresourceOperation, OperationType::SUBRESOURCE)][strtolower($method)] = $this->addSubresourceOperation($v3, $subresourceOperation, $definitions, $operationId, $resourceMetadata);
            }
        }

        $definitions->ksort();
        $paths->ksort();

        return $this->computeDoc($v3, $object, $definitions, $paths, $context);
    }

    /**
     * Updates the list of entries in the paths collection.
     */
    private function addPaths(bool $v3, \ArrayObject $paths, \ArrayObject $definitions, string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, string $operationType, \ArrayObject $links)
    {
        if (null === $operations = OperationType::COLLECTION === $operationType ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return;
        }

        foreach ($operations as $operationName => $operation) {
            $path = $this->getPath($resourceShortName, $operationName, $operation, $operationType);
            if ($this->operationMethodResolver) {
                $method = OperationType::ITEM === $operationType ? $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName) : $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
            } else {
                $method = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'method', 'GET');
            }

            $paths[$path][strtolower($method)] = $this->getPathOperation($v3, $operationName, $operation, $method, $operationType, $resourceClass, $resourceMetadata, $definitions, $links);
        }
    }

    /**
     * Gets the path for an operation.
     *
     * If the path ends with the optional _format parameter, it is removed
     * as optional path parameters are not yet supported.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/issues/93
     */
    private function getPath(string $resourceShortName, string $operationName, array $operation, string $operationType): string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return $path;
    }

    /**
     * Gets a path Operation Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operation-object
     */
    private function getPathOperation(bool $v3, string $operationName, array $operation, string $method, string $operationType, string $resourceClass, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, \ArrayObject $links): \ArrayObject
    {
        $pathOperation = new \ArrayObject($operation[$v3 ? 'openapi_context' : 'swagger_context'] ?? []);
        $resourceShortName = $resourceMetadata->getShortName();
        $pathOperation['tags'] ?? $pathOperation['tags'] = [$resourceShortName];
        $pathOperation['operationId'] ?? $pathOperation['operationId'] = lcfirst($operationName).ucfirst($resourceShortName).ucfirst($operationType);
        if ($v3 && 'GET' === $method && OperationType::ITEM === $operationType && $link = $this->getLinkObject($resourceClass, $pathOperation['operationId'], $this->getPath($resourceShortName, $operationName, $operation, $operationType))) {
            $links[$pathOperation['operationId']] = $link;
        }
        if ($resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'deprecation_reason', null, true)) {
            $pathOperation['deprecated'] = true;
        }

        if (null === $this->formatsProvider) {
            $requestFormats = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'input_formats', [], true);
            $responseFormats = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'output_formats', [], true);
        } else {
            $requestFormats = $responseFormats = $this->formatsProvider->getFormatsFromOperation($resourceClass, $operationName, $operationType);
        }

        $requestMimeTypes = $this->flattenMimeTypes($requestFormats);
        $responseMimeTypes = $this->flattenMimeTypes($responseFormats);
        switch ($method) {
            case 'GET':
                return $this->updateGetOperation($v3, $pathOperation, $responseMimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'POST':
                return $this->updatePostOperation($v3, $pathOperation, $requestMimeTypes, $responseMimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions, $links);
            case 'PATCH':
                $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Updates the %s resource.', $resourceShortName);
            // no break
            case 'PUT':
                return $this->updatePutOperation($v3, $pathOperation, $requestMimeTypes, $responseMimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'DELETE':
                return $this->updateDeleteOperation($v3, $pathOperation, $resourceShortName, $operationType, $operationName, $resourceMetadata);
        }

        return $pathOperation;
    }

    /**
     * @return array the update message as first value, and if the schema is defined as second
     */
    private function addSchemas(bool $v3, array $message, \ArrayObject $definitions, string $resourceClass, string $operationType, string $operationName, array $mimeTypes, string $type = Schema::TYPE_OUTPUT, bool $forceCollection = false): array
    {
        if (!$v3) {
            $jsonSchema = $this->getJsonSchema($v3, $definitions, $resourceClass, $type, $operationType, $operationName, 'json', null, $forceCollection);
            if (!$jsonSchema->isDefined()) {
                return [$message, false];
            }

            $message['schema'] = $jsonSchema->getArrayCopy(false);

            return [$message, true];
        }

        foreach ($mimeTypes as $mimeType => $format) {
            $jsonSchema = $this->getJsonSchema($v3, $definitions, $resourceClass, $type, $operationType, $operationName, $format, null, $forceCollection);
            if (!$jsonSchema->isDefined()) {
                return [$message, false];
            }

            $message['content'][$mimeType] = ['schema' => $jsonSchema->getArrayCopy(false)];
        }

        return [$message, true];
    }

    private function updateGetOperation(bool $v3, \ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions): \ArrayObject
    {
        $successStatus = (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '200');

        if (!$v3) {
            $pathOperation['produces'] ?? $pathOperation['produces'] = array_keys($mimeTypes);
        }

        if (OperationType::COLLECTION === $operationType) {
            $outputResourseShortName = $resourceMetadata->getCollectionOperations()[$operationName]['output']['name'] ?? $resourceShortName;
            $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves the collection of %s resources.', $outputResourseShortName);

            $successResponse = ['description' => sprintf('%s collection response', $outputResourseShortName)];
            [$successResponse] = $this->addSchemas($v3, $successResponse, $definitions, $resourceClass, $operationType, $operationName, $mimeTypes);

            $pathOperation['responses'] ?? $pathOperation['responses'] = [$successStatus => $successResponse];
            $pathOperation['parameters'] ?? $pathOperation['parameters'] = $this->getFiltersParameters($v3, $resourceClass, $operationName, $resourceMetadata);

            $this->addPaginationParameters($v3, $resourceMetadata, OperationType::COLLECTION, $operationName, $pathOperation);

            return $pathOperation;
        }

        $outputResourseShortName = $resourceMetadata->getItemOperations()[$operationName]['output']['name'] ?? $resourceShortName;
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $outputResourseShortName);

        $pathOperation = $this->addItemOperationParameters($v3, $pathOperation, $operationType, $operationName, $resourceMetadata);

        $successResponse = ['description' => sprintf('%s resource response', $outputResourseShortName)];
        [$successResponse] = $this->addSchemas($v3, $successResponse, $definitions, $resourceClass, $operationType, $operationName, $mimeTypes);

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            $successStatus => $successResponse,
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    private function addPaginationParameters(bool $v3, ResourceMetadata $resourceMetadata, string $operationType, string $operationName, \ArrayObject $pathOperation)
    {
        if ($this->paginationEnabled && $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'pagination_enabled', true, true)) {
            $paginationParameter = [
                'name' => $this->paginationPageParameterName,
                'in' => 'query',
                'required' => false,
                'description' => 'The collection page number',
            ];
            $v3 ? $paginationParameter['schema'] = [
                'type' => 'integer',
                'default' => 1,
            ] : $paginationParameter['type'] = 'integer';
            $pathOperation['parameters'][] = $paginationParameter;

            if ($resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
                $itemPerPageParameter = [
                    'name' => $this->itemsPerPageParameterName,
                    'in' => 'query',
                    'required' => false,
                    'description' => 'The number of items per page',
                ];
                if ($v3) {
                    $itemPerPageParameter['schema'] = [
                        'type' => 'integer',
                        'default' => $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'pagination_items_per_page', 30, true),
                        'minimum' => 0,
                    ];

                    $maxItemsPerPage = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'maximum_items_per_page', null, true);
                    if (null !== $maxItemsPerPage) {
                        @trigger_error('The "maximum_items_per_page" option has been deprecated since API Platform 2.5 in favor of "pagination_maximum_items_per_page" and will be removed in API Platform 3.', \E_USER_DEPRECATED);
                    }
                    $maxItemsPerPage = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'pagination_maximum_items_per_page', $maxItemsPerPage, true);

                    if (null !== $maxItemsPerPage) {
                        $itemPerPageParameter['schema']['maximum'] = $maxItemsPerPage;
                    }
                } else {
                    $itemPerPageParameter['type'] = 'integer';
                }

                $pathOperation['parameters'][] = $itemPerPageParameter;
            }
        }

        if ($this->paginationEnabled && $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'pagination_client_enabled', $this->paginationClientEnabled, true)) {
            $paginationEnabledParameter = [
                'name' => $this->paginationClientEnabledParameterName,
                'in' => 'query',
                'required' => false,
                'description' => 'Enable or disable pagination',
            ];
            $v3 ? $paginationEnabledParameter['schema'] = ['type' => 'boolean'] : $paginationEnabledParameter['type'] = 'boolean';
            $pathOperation['parameters'][] = $paginationEnabledParameter;
        }
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    private function addSubresourceOperation(bool $v3, array $subresourceOperation, \ArrayObject $definitions, string $operationId, ResourceMetadata $resourceMetadata): \ArrayObject
    {
        $operationName = 'get'; // TODO: we might want to extract that at some point to also support other subresource operations
        $collection = $subresourceOperation['collection'] ?? false;

        $subResourceMetadata = $this->resourceMetadataFactory->create($subresourceOperation['resource_class']);

        $pathOperation = new \ArrayObject([]);
        $pathOperation['tags'] = $subresourceOperation['shortNames'];
        $pathOperation['operationId'] = $operationId;
        $pathOperation['summary'] = sprintf('Retrieves %s%s resource%s.', $subresourceOperation['collection'] ? 'the collection of ' : 'a ', $subresourceOperation['shortNames'][0], $subresourceOperation['collection'] ? 's' : '');

        if (null === $this->formatsProvider) {
            // TODO: Subresource operation metadata aren't available by default, for now we have to fallback on default formats.
            // TODO: A better approach would be to always populate the subresource operation array.
            $subResourceMetadata = $this
                ->resourceMetadataFactory
                ->create($subresourceOperation['resource_class']);

            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                $subResourceMetadata = $this->transformResourceToResourceMetadata($subResourceMetadata[0]);
            }

            $responseFormats = $subResourceMetadata->getTypedOperationAttribute(OperationType::SUBRESOURCE, $operationName, 'output_formats', $this->formats, true);
        } else {
            $responseFormats = $this->formatsProvider->getFormatsFromOperation($subresourceOperation['resource_class'], $operationName, OperationType::SUBRESOURCE);
        }

        $mimeTypes = $this->flattenMimeTypes($responseFormats);

        if (!$v3) {
            $pathOperation['produces'] = array_keys($mimeTypes);
        }

        $successResponse = [
            'description' => sprintf('%s %s response', $subresourceOperation['shortNames'][0], $collection ? 'collection' : 'resource'),
        ];
        [$successResponse] = $this->addSchemas($v3, $successResponse, $definitions, $subresourceOperation['resource_class'], OperationType::SUBRESOURCE, $operationName, $mimeTypes, Schema::TYPE_OUTPUT, $collection);

        $pathOperation['responses'] = ['200' => $successResponse, '404' => ['description' => 'Resource not found']];

        // Avoid duplicates parameters when there is a filter on a subresource identifier
        $parametersMemory = [];
        $pathOperation['parameters'] = [];
        foreach ($subresourceOperation['identifiers'] as $parameterName => [$class, $identifier, $hasIdentifier]) {
            if (false === strpos($subresourceOperation['path'], sprintf('{%s}', $parameterName))) {
                continue;
            }

            $parameter = ['name' => $parameterName, 'in' => 'path', 'required' => true];
            $v3 ? $parameter['schema'] = ['type' => 'string'] : $parameter['type'] = 'string';
            $pathOperation['parameters'][] = $parameter;
            $parametersMemory[] = $parameterName;
        }

        if ($parameters = $this->getFiltersParameters($v3, $subresourceOperation['resource_class'], $operationName, $subResourceMetadata)) {
            foreach ($parameters as $parameter) {
                if (!\in_array($parameter['name'], $parametersMemory, true)) {
                    $pathOperation['parameters'][] = $parameter;
                }
            }
        }

        if ($subresourceOperation['collection']) {
            $this->addPaginationParameters($v3, $subResourceMetadata, OperationType::SUBRESOURCE, $subresourceOperation['operation_name'], $pathOperation);
        }

        return $pathOperation;
    }

    private function updatePostOperation(bool $v3, \ArrayObject $pathOperation, array $requestMimeTypes, array $responseMimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions, \ArrayObject $links): \ArrayObject
    {
        if (!$v3) {
            $pathOperation['consumes'] ?? $pathOperation['consumes'] = array_keys($requestMimeTypes);
            $pathOperation['produces'] ?? $pathOperation['produces'] = array_keys($responseMimeTypes);
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Creates a %s resource.', $resourceShortName);

        if (OperationType::ITEM === $operationType) {
            $pathOperation = $this->addItemOperationParameters($v3, $pathOperation, $operationType, $operationName, $resourceMetadata);
        }

        $successResponse = ['description' => sprintf('%s resource created', $resourceShortName)];
        [$successResponse, $defined] = $this->addSchemas($v3, $successResponse, $definitions, $resourceClass, $operationType, $operationName, $responseMimeTypes);

        if ($defined && $v3 && ($links[$key = 'get'.ucfirst($resourceShortName).ucfirst(OperationType::ITEM)] ?? null)) {
            $successResponse['links'] = [ucfirst($key) => $links[$key]];
        }

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '201') => $successResponse,
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
            '422' => ['description' => 'Unprocessable entity'],
        ];

        return $this->addRequestBody($v3, $pathOperation, $definitions, $resourceClass, $resourceShortName, $operationType, $operationName, $requestMimeTypes);
    }

    private function updatePutOperation(bool $v3, \ArrayObject $pathOperation, array $requestMimeTypes, array $responseMimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions): \ArrayObject
    {
        if (!$v3) {
            $pathOperation['consumes'] ?? $pathOperation['consumes'] = array_keys($requestMimeTypes);
            $pathOperation['produces'] ?? $pathOperation['produces'] = array_keys($responseMimeTypes);
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Replaces the %s resource.', $resourceShortName);

        $pathOperation = $this->addItemOperationParameters($v3, $pathOperation, $operationType, $operationName, $resourceMetadata);

        $successResponse = ['description' => sprintf('%s resource updated', $resourceShortName)];
        [$successResponse] = $this->addSchemas($v3, $successResponse, $definitions, $resourceClass, $operationType, $operationName, $responseMimeTypes);

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '200') => $successResponse,
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
            '422' => ['description' => 'Unprocessable entity'],
        ];

        return $this->addRequestBody($v3, $pathOperation, $definitions, $resourceClass, $resourceShortName, $operationType, $operationName, $requestMimeTypes, true);
    }

    private function addRequestBody(bool $v3, \ArrayObject $pathOperation, \ArrayObject $definitions, string $resourceClass, string $resourceShortName, string $operationType, string $operationName, array $requestMimeTypes, bool $put = false)
    {
        if (isset($pathOperation['requestBody'])) {
            return $pathOperation;
        }

        [$message, $defined] = $this->addSchemas($v3, [], $definitions, $resourceClass, $operationType, $operationName, $requestMimeTypes, Schema::TYPE_INPUT);
        if (!$defined) {
            return $pathOperation;
        }

        $description = sprintf('The %s %s resource', $put ? 'updated' : 'new', $resourceShortName);
        if ($v3) {
            $pathOperation['requestBody'] = $message + ['description' => $description];

            return $pathOperation;
        }

        if (!$this->hasBodyParameter($pathOperation['parameters'] ?? [])) {
            $pathOperation['parameters'][] = [
                'name' => lcfirst($resourceShortName),
                'in' => 'body',
                'description' => $description,
            ] + $message;
        }

        return $pathOperation;
    }

    private function hasBodyParameter(array $parameters): bool
    {
        foreach ($parameters as $parameter) {
            if (\array_key_exists('in', $parameter) && 'body' === $parameter['in']) {
                return true;
            }
        }

        return false;
    }

    private function updateDeleteOperation(bool $v3, \ArrayObject $pathOperation, string $resourceShortName, string $operationType, string $operationName, ResourceMetadata $resourceMetadata): \ArrayObject
    {
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Removes the %s resource.', $resourceShortName);
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '204') => ['description' => sprintf('%s resource deleted', $resourceShortName)],
            '404' => ['description' => 'Resource not found'],
        ];

        return $this->addItemOperationParameters($v3, $pathOperation, $operationType, $operationName, $resourceMetadata);
    }

    private function addItemOperationParameters(bool $v3, \ArrayObject $pathOperation, string $operationType, string $operationName, ResourceMetadata $resourceMetadata): \ArrayObject
    {
        $identifiers = (array) $resourceMetadata
                ->getTypedOperationAttribute(OperationType::ITEM, $operationName, 'identifiers', ['id'], true);
        if (\count($identifiers) > 1 ? $resourceMetadata->getAttribute('composite_identifier', true) : false) {
            $identifiers = ['id'];
        }

        if (!isset($pathOperation['parameters'])) {
            $pathOperation['parameters'] = [];
        }

        foreach ($identifiers as $parameterName => $identifier) {
            $parameter = [
                'name' => \is_string($parameterName) ? $parameterName : $identifier,
                'in' => 'path',
                'required' => true,
            ];
            $v3 ? $parameter['schema'] = ['type' => 'string'] : $parameter['type'] = 'string';
            $pathOperation['parameters'][] = $parameter;
        }

        return $pathOperation;
    }

    private function getJsonSchema(bool $v3, \ArrayObject $definitions, string $resourceClass, string $type, ?string $operationType, ?string $operationName, string $format = 'json', ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = new Schema($v3 ? Schema::VERSION_OPENAPI : Schema::VERSION_SWAGGER);
        $schema->setDefinitions($definitions);

        return $this->jsonSchemaFactory->buildSchema($resourceClass, $format, $type, $operationType, $operationName, $schema, $serializerContext, $forceCollection);
    }

    private function computeDoc(bool $v3, Documentation $documentation, \ArrayObject $definitions, \ArrayObject $paths, array $context): array
    {
        $baseUrl = $context[self::BASE_URL] ?? $this->defaultContext[self::BASE_URL];

        if ($v3) {
            $docs = ['openapi' => self::OPENAPI_VERSION];
            if ('/' !== $baseUrl && '' !== $baseUrl) {
                $docs['servers'] = [['url' => $baseUrl]];
            }
        } else {
            $docs = [
                'swagger' => self::SWAGGER_VERSION,
                'basePath' => $baseUrl,
            ];
        }

        $docs += [
            'info' => [
                'title' => $documentation->getTitle(),
                'version' => $documentation->getVersion(),
            ],
            'paths' => $paths,
        ];

        if ('' !== $description = $documentation->getDescription()) {
            $docs['info']['description'] = $description;
        }

        $securityDefinitions = [];
        $security = [];

        if ($this->oauthEnabled) {
            $oauthAttributes = [
                'authorizationUrl' => $this->oauthAuthorizationUrl,
                'scopes' => new \ArrayObject($this->oauthScopes),
            ];

            if ($this->oauthTokenUrl) {
                $oauthAttributes['tokenUrl'] = $this->oauthTokenUrl;
            }

            $securityDefinitions['oauth'] = [
                'type' => $this->oauthType,
                'description' => sprintf(
                    'OAuth 2.0 %s Grant',
                    strtolower(preg_replace('/[A-Z]/', ' \\0', lcfirst($this->oauthFlow)))
                ),
            ];

            if ($v3) {
                $securityDefinitions['oauth']['flows'] = [
                    $this->oauthFlow => $oauthAttributes,
                ];
            } else {
                $securityDefinitions['oauth']['flow'] = $this->oauthFlow;
                $securityDefinitions['oauth'] = array_merge($securityDefinitions['oauth'], $oauthAttributes);
            }

            $security[] = ['oauth' => []];
        }

        foreach ($this->apiKeys as $key => $apiKey) {
            $name = $apiKey['name'];
            $type = $apiKey['type'];

            $securityDefinitions[$key] = [
                'type' => 'apiKey',
                'in' => $type,
                'description' => sprintf('Value for the %s %s', $name, 'query' === $type ? sprintf('%s parameter', $type) : $type),
                'name' => $name,
            ];

            $security[] = [$key => []];
        }

        if ($securityDefinitions && $security) { // @phpstan-ignore-line false positive
            $docs['security'] = $security;
            if (!$v3) {
                $docs['securityDefinitions'] = $securityDefinitions;
            }
        }

        if ($v3) {
            if (\count($definitions) + \count($securityDefinitions)) {
                $docs['components'] = [];
                if (\count($definitions)) {
                    $docs['components']['schemas'] = $definitions;
                }
                if (\count($securityDefinitions)) {
                    $docs['components']['securitySchemes'] = $securityDefinitions;
                }
            }
        } elseif (\count($definitions) > 0) {
            $docs['definitions'] = $definitions;
        }

        return $docs;
    }

    /**
     * Gets parameters corresponding to enabled filters.
     */
    private function getFiltersParameters(bool $v3, string $resourceClass, string $operationName, ResourceMetadata $resourceMetadata): array
    {
        if (null === $this->filterLocator) {
            return [];
        }

        $parameters = [];
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);
        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($resourceClass) as $name => $data) {
                $parameter = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => $data['required'],
                ];

                $type = \in_array($data['type'], Type::$builtinTypes, true) ? $this->jsonSchemaTypeFactory->getType(new Type($data['type'], false, null, $data['is_collection'] ?? false)) : ['type' => 'string'];
                $v3 ? $parameter['schema'] = $type : $parameter += $type;

                if ($v3 && isset($data['schema'])) {
                    $parameter['schema'] = $data['schema'];
                }

                if ('array' === ($type['type'] ?? '')) {
                    $deepObject = \in_array($data['type'], [Type::BUILTIN_TYPE_ARRAY, Type::BUILTIN_TYPE_OBJECT], true);

                    if ($v3) {
                        $parameter['style'] = $deepObject ? 'deepObject' : 'form';
                        $parameter['explode'] = true;
                    } else {
                        $parameter['collectionFormat'] = $deepObject ? 'csv' : 'multi';
                    }
                }

                $key = $v3 ? 'openapi' : 'swagger';
                if (isset($data[$key])) {
                    $parameter = $data[$key] + $parameter;
                }

                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return self::FORMAT === $format && ($data instanceof Documentation || $this->openApiNormalizer && $data instanceof OpenApi);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
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
     * https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md#linkObject.
     */
    private function getLinkObject(string $resourceClass, string $operationId, string $path): array
    {
        $linkObject = $identifiers = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            if (!$propertyMetadata->isIdentifier()) {
                continue;
            }

            $linkObject['parameters'][$propertyName] = sprintf('$response.body#/%s', $propertyName);
            $identifiers[] = $propertyName;
        }

        if (!$linkObject) {
            return [];
        }
        $linkObject['operationId'] = $operationId;
        $linkObject['description'] = 1 === \count($identifiers) ? sprintf('The `%1$s` value returned in the response can be used as the `%1$s` parameter in `GET %2$s`.', $identifiers[0], $path) : sprintf('The values returned in the response can be used in `GET %s`.', $path);

        return $linkObject;
    }
}
