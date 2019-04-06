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
use ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface;
use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $operationPathResolver;
    private $nameConverter;
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
    private $formatsProvider;
    private $defaultContext = [
        self::BASE_URL => '/',
        self::SPEC_VERSION => 2,
        ApiGatewayNormalizer::API_GATEWAY => false,
    ];

    /**
     * @param ContainerInterface|FilterCollection|null $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver, OperationPathResolverInterface $operationPathResolver, UrlGeneratorInterface $urlGenerator = null, $filterLocator = null, NameConverterInterface $nameConverter = null, bool $oauthEnabled = false, string $oauthType = '', string $oauthFlow = '', string $oauthTokenUrl = '', string $oauthAuthorizationUrl = '', array $oauthScopes = [], array $apiKeys = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null, bool $paginationEnabled = true, string $paginationPageParameterName = 'page', bool $clientItemsPerPage = false, string $itemsPerPageParameterName = 'itemsPerPage', OperationAwareFormatsProviderInterface $formatsProvider = null, bool $paginationClientEnabled = false, string $paginationClientEnabledParameterName = 'pagination', array $defaultContext = [])
    {
        if ($urlGenerator) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.1 and will be removed in 3.0.', UrlGeneratorInterface::class, __METHOD__), E_USER_DEPRECATED);
        }

        $this->setFilterLocator($filterLocator, true);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->operationPathResolver = $operationPathResolver;
        $this->nameConverter = $nameConverter;
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
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
        $this->formatsProvider = $formatsProvider;
        $this->paginationClientEnabled = $paginationClientEnabled;
        $this->paginationClientEnabledParameterName = $paginationClientEnabledParameterName;

        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $v3 = 3 === ($context['spec_version'] ?? $this->defaultContext['spec_version']) && !($context['api_gateway'] ?? $this->defaultContext['api_gateway']);

        $mimeTypes = $object->getMimeTypes();
        $definitions = new \ArrayObject();
        $paths = new \ArrayObject();
        $links = new \ArrayObject();

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $resourceShortName = $resourceMetadata->getShortName();

            // Items needs to be parsed first to be able to reference the lines from the collection operation
            $this->addPaths($v3, $paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::ITEM, $links);
            $this->addPaths($v3, $paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::COLLECTION, $links);

            if (null === $this->subresourceOperationFactory) {
                continue;
            }

            foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $subresourceOperation) {
                $operationName = 'get';
                $subResourceMetadata = $this->resourceMetadataFactory->create($subresourceOperation['resource_class']);
                $serializerContext = $this->getSerializerContext(OperationType::SUBRESOURCE, false, $subResourceMetadata, $operationName);

                $responseDefinitionKey = false;
                $outputMetadata = $resourceMetadata->getTypedOperationAttribute(OperationType::SUBRESOURCE, $operationName, 'output', ['class' => $subresourceOperation['resource_class']], true);
                if (null !== $outputClass = $outputMetadata['class'] ?? null) {
                    $responseDefinitionKey = $this->getDefinition($v3, $definitions, $subResourceMetadata, $subresourceOperation['resource_class'], $outputClass, $serializerContext);
                }

                $pathOperation = new \ArrayObject([]);
                $pathOperation['tags'] = $subresourceOperation['shortNames'];
                $pathOperation['operationId'] = $operationId;

                if (null !== $this->formatsProvider) {
                    $responseFormats = $this->formatsProvider->getFormatsFromOperation($subresourceOperation['resource_class'], $operationName, OperationType::SUBRESOURCE);
                    $responseMimeTypes = $this->extractMimeTypes($responseFormats);
                }
                if (!$v3) {
                    $pathOperation['produces'] = $responseMimeTypes ?? $mimeTypes;
                }

                if ($subresourceOperation['collection']) {
                    $baseSuccessResponse = ['description' => sprintf('%s collection response', end($subresourceOperation['shortNames']))];

                    if ($responseDefinitionKey) {
                        if ($v3) {
                            $baseSuccessResponse['content'] = array_fill_keys($responseMimeTypes ?? $mimeTypes, ['schema' => ['type' => 'array', 'items' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)]]]);
                        } else {
                            $baseSuccessResponse['schema'] = ['type' => 'array', 'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)]];
                        }
                    }
                } else {
                    $baseSuccessResponse = ['description' => sprintf('%s resource response', end($subresourceOperation['shortNames']))];

                    if ($responseDefinitionKey) {
                        if ($v3) {
                            $baseSuccessResponse['content'] = array_fill_keys($responseMimeTypes ?? $mimeTypes, ['schema' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)]]);
                        } else {
                            $baseSuccessResponse['schema'] = ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)];
                        }
                    }
                }

                $pathOperation['summary'] = sprintf('Retrieves %s%s resource%s.', $subresourceOperation['collection'] ? 'the collection of ' : 'a ', end($subresourceOperation['shortNames']), $subresourceOperation['collection'] ? 's' : '');
                $pathOperation['responses'] = [
                    (string) $resourceMetadata->getSubresourceOperationAttribute($operationName, 'status', '200') => $baseSuccessResponse,
                    '404' => ['description' => 'Resource not found'],
                ];

                // Avoid duplicates parameters when there is a filter on a subresource identifier
                $parametersMemory = [];
                $pathOperation['parameters'] = [];

                foreach ($subresourceOperation['identifiers'] as [$identifier, , $hasIdentifier]) {
                    if (true === $hasIdentifier) {
                        $parameter = [
                            'name' => $identifier,
                            'in' => 'path',
                            'required' => true,
                        ];
                        $v3 ? $parameter['schema'] = ['type' => 'string'] : $parameter['type'] = 'string';

                        $pathOperation['parameters'][] = $parameter;
                        $parametersMemory[] = $identifier;
                    }
                }

                if ($parameters = $this->getFiltersParameters($v3, $subresourceOperation['resource_class'], $operationName, $subResourceMetadata, $definitions, $serializerContext)) {
                    foreach ($parameters as $parameter) {
                        if (!\in_array($parameter['name'], $parametersMemory, true)) {
                            $pathOperation['parameters'][] = $parameter;
                        }
                    }
                }

                $paths[$this->getPath(end($subresourceOperation['shortNames']), $subresourceOperation['route_name'], $subresourceOperation, OperationType::SUBRESOURCE)] = new \ArrayObject(['get' => $pathOperation]);
            }
        }

        $definitions->ksort();
        $paths->ksort();

        return $this->computeDoc($v3, $object, $definitions, $paths, $context);
    }

    /**
     * Updates the list of entries in the paths collection.
     */
    private function addPaths(bool $v3, \ArrayObject $paths, \ArrayObject $definitions, string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, array $mimeTypes, string $operationType, \ArrayObject $links)
    {
        if (null === $operations = OperationType::COLLECTION === $operationType ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return;
        }

        foreach ($operations as $operationName => $operation) {
            $path = $this->getPath($resourceShortName, $operationName, $operation, $operationType);
            $method = OperationType::ITEM === $operationType ? $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName) : $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);

            $paths[$path][strtolower($method)] = $this->getPathOperation($v3, $operationName, $operation, $method, $operationType, $resourceClass, $resourceMetadata, $mimeTypes, $definitions, $links);
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
     *
     * @param string[] $mimeTypes
     */
    private function getPathOperation(bool $v3, string $operationName, array $operation, string $method, string $operationType, string $resourceClass, ResourceMetadata $resourceMetadata, array $mimeTypes, \ArrayObject $definitions, \ArrayObject $links): \ArrayObject
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
        if (null !== $this->formatsProvider) {
            $responseFormats = $this->formatsProvider->getFormatsFromOperation($resourceClass, $operationName, $operationType);
            $responseMimeTypes = $this->extractMimeTypes($responseFormats);
        }
        switch ($method) {
            case 'GET':
                return $this->updateGetOperation($v3, $pathOperation, $responseMimeTypes ?? $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'POST':
                return $this->updatePostOperation($v3, $pathOperation, $responseMimeTypes ?? $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions, $links);
            case 'PATCH':
                $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Updates the %s resource.', $resourceShortName);
            // no break
            case 'PUT':
                return $this->updatePutOperation($v3, $pathOperation, $responseMimeTypes ?? $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'DELETE':
                return $this->updateDeleteOperation($v3, $pathOperation, $resourceShortName, $operationType, $operationName, $resourceMetadata);
        }

        return $pathOperation;
    }

    private function updateGetOperation(bool $v3, \ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions): \ArrayObject
    {
        $serializerContext = $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName);

        $responseDefinitionKey = false;
        $outputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'output', ['class' => $resourceClass], true);
        if (null !== $outputClass = $outputMetadata['class'] ?? null) {
            $responseDefinitionKey = $this->getDefinition($v3, $definitions, $resourceMetadata, $resourceClass, $outputClass, $serializerContext);
        }

        $successStatus = (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '200');

        if (!$v3) {
            $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        }

        if (OperationType::COLLECTION === $operationType) {
            $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves the collection of %s resources.', $resourceShortName);

            $successResponse = ['description' => sprintf('%s collection response', $resourceShortName)];

            if ($responseDefinitionKey) {
                if ($v3) {
                    $successResponse['content'] = array_fill_keys($mimeTypes, [
                        'schema' => [
                            'type' => 'array',
                            'items' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)],
                        ],
                    ]);
                } else {
                    $successResponse['schema'] = [
                        'type' => 'array',
                        'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                    ];
                }
            }

            $pathOperation['responses'] ?? $pathOperation['responses'] = [$successStatus => $successResponse];
            $pathOperation['parameters'] ?? $pathOperation['parameters'] = $this->getFiltersParameters($v3, $resourceClass, $operationName, $resourceMetadata, $definitions, $serializerContext);

            if ($this->paginationEnabled && $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', true, true)) {
                $paginationParameter = [
                    'name' => $this->paginationPageParameterName,
                    'in' => 'query',
                    'required' => false,
                    'description' => 'The collection page number',
                ];
                $v3 ? $paginationParameter['schema'] = ['type' => 'integer'] : $paginationParameter['type'] = 'integer';
                $pathOperation['parameters'][] = $paginationParameter;

                if ($resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
                    $itemPerPageParameter = [
                        'name' => $this->itemsPerPageParameterName,
                        'in' => 'query',
                        'required' => false,
                        'description' => 'The number of items per page',
                    ];
                    $v3 ? $itemPerPageParameter['schema'] = ['type' => 'integer'] : $itemPerPageParameter['type'] = 'integer';

                    $pathOperation['parameters'][] = $itemPerPageParameter;
                }
            }

            if ($this->paginationEnabled && $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_enabled', $this->paginationClientEnabled, true)) {
                $paginationEnabledParameter = [
                    'name' => $this->paginationClientEnabledParameterName,
                    'in' => 'query',
                    'required' => false,
                    'description' => 'Enable or disable pagination',
                ];
                $v3 ? $paginationEnabledParameter['schema'] = ['type' => 'boolean'] : $paginationEnabledParameter['type'] = 'boolean';
                $pathOperation['parameters'][] = $paginationEnabledParameter;
            }

            return $pathOperation;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $resourceShortName);

        $parameter = [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
        ];
        $v3 ? $parameter['schema'] = ['type' => 'string'] : $parameter['type'] = 'string';
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [$parameter];

        $successResponse = ['description' => sprintf('%s resource response', $resourceShortName)];
        if ($responseDefinitionKey) {
            if ($v3) {
                $successResponse['content'] = array_fill_keys($mimeTypes, ['schema' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)]]);
            } else {
                $successResponse['schema'] = ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)];
            }
        }

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            $successStatus => $successResponse,
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    private function updatePostOperation(bool $v3, \ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions, \ArrayObject $links): \ArrayObject
    {
        if (!$v3) {
            $pathOperation['consumes'] ?? $pathOperation['consumes'] = $mimeTypes;
            $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Creates a %s resource.', $resourceShortName);

        $responseDefinitionKey = false;
        $outputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'output', ['class' => $resourceClass], true);
        if (null !== $outputClass = $outputMetadata['class'] ?? null) {
            $responseDefinitionKey = $this->getDefinition($v3, $definitions, $resourceMetadata, $resourceClass, $outputClass, $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName));
        }

        $successResponse = ['description' => sprintf('%s resource created', $resourceShortName)];
        if ($responseDefinitionKey) {
            if ($v3) {
                $successResponse['content'] = array_fill_keys($mimeTypes, ['schema' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)]]);
                if ($links[$key = 'get'.ucfirst($resourceShortName).ucfirst(OperationType::ITEM)] ?? null) {
                    $successResponse['links'] = [ucfirst($key) => $links[$key]];
                }
            } else {
                $successResponse['schema'] = ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)];
            }
        }

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '201') => $successResponse,
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        $inputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'input', ['class' => $resourceClass], true);
        if (null === $inputClass = $inputMetadata['class'] ?? null) {
            return $pathOperation;
        }

        $requestDefinitionKey = $this->getDefinition($v3, $definitions, $resourceMetadata, $resourceClass, $inputClass, $this->getSerializerContext($operationType, true, $resourceMetadata, $operationName));
        if ($v3) {
            $pathOperation['requestBody'] ?? $pathOperation['requestBody'] = [
                'content' => array_fill_keys($mimeTypes, ['schema' => ['$ref' => sprintf('#/components/schemas/%s', $requestDefinitionKey)]]),
                'description' => sprintf('The new %s resource', $resourceShortName),
            ];
        } else {
            $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
                'name' => lcfirst($resourceShortName),
                'in' => 'body',
                'description' => sprintf('The new %s resource', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $requestDefinitionKey)],
            ]];
        }

        return $pathOperation;
    }

    private function updatePutOperation(bool $v3, \ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions): \ArrayObject
    {
        if (!$v3) {
            $pathOperation['consumes'] ?? $pathOperation['consumes'] = $mimeTypes;
            $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Replaces the %s resource.', $resourceShortName);

        $parameter = [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
        ];
        $v3 ? $parameter['schema'] = ['type' => 'string'] : $parameter['type'] = 'string';
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [$parameter];

        $responseDefinitionKey = false;
        $outputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'output', ['class' => $resourceClass], true);
        if (null !== $outputClass = $outputMetadata['class'] ?? null) {
            $responseDefinitionKey = $this->getDefinition($v3, $definitions, $resourceMetadata, $resourceClass, $outputClass, $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName));
        }

        $successResponse = ['description' => sprintf('%s resource updated', $resourceShortName)];
        if ($responseDefinitionKey) {
            if ($v3) {
                $successResponse['content'] = array_fill_keys($mimeTypes, ['schema' => ['$ref' => sprintf('#/components/schemas/%s', $responseDefinitionKey)]]);
            } else {
                $successResponse['schema'] = ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)];
            }
        }

        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '200') => $successResponse,
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        $inputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'input', ['class' => $resourceClass], true);
        if (null === $inputClass = $inputMetadata['class'] ?? null) {
            return $pathOperation;
        }

        $requestDefinitionKey = $this->getDefinition($v3, $definitions, $resourceMetadata, $resourceClass, $inputClass, $this->getSerializerContext($operationType, true, $resourceMetadata, $operationName));
        if ($v3) {
            $pathOperation['requestBody'] ?? $pathOperation['requestBody'] = [
                'content' => array_fill_keys($mimeTypes, ['schema' => ['$ref' => sprintf('#/components/schemas/%s', $requestDefinitionKey)]]),
                'description' => sprintf('The updated %s resource', $resourceShortName),
            ];
        } else {
            $pathOperation['parameters'][] = [
                'name' => lcfirst($resourceShortName),
                'in' => 'body',
                'description' => sprintf('The updated %s resource', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $requestDefinitionKey)],
            ];
        }

        return $pathOperation;
    }

    private function updateDeleteOperation(bool $v3, \ArrayObject $pathOperation, string $resourceShortName, string $operationType, string $operationName, ResourceMetadata $resourceMetadata): \ArrayObject
    {
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Removes the %s resource.', $resourceShortName);
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            (string) $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'status', '204') => ['description' => sprintf('%s resource deleted', $resourceShortName)],
            '404' => ['description' => 'Resource not found'],
        ];

        $parameter = [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
        ];
        $v3 ? $parameter['schema'] = ['type' => 'string'] : $parameter['type'] = 'string';

        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [$parameter];

        return $pathOperation;
    }

    private function getDefinition(bool $v3, \ArrayObject $definitions, ResourceMetadata $resourceMetadata, string $resourceClass, ?string $publicClass, array $serializerContext = null): string
    {
        $keyPrefix = $resourceMetadata->getShortName();
        if (null !== $publicClass && $resourceClass !== $publicClass) {
            $keyPrefix .= ':'.md5($publicClass);
        }

        if (isset($serializerContext[self::SWAGGER_DEFINITION_NAME])) {
            $definitionKey = sprintf('%s-%s', $keyPrefix, $serializerContext[self::SWAGGER_DEFINITION_NAME]);
        } else {
            $definitionKey = $this->getDefinitionKey($keyPrefix, (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []));
        }

        if (!isset($definitions[$definitionKey])) {
            $definitions[$definitionKey] = [];  // Initialize first to prevent infinite loop
            $definitions[$definitionKey] = $this->getDefinitionSchema($v3, $publicClass ?? $resourceClass, $resourceMetadata, $definitions, $serializerContext);
        }

        return $definitionKey;
    }

    private function getDefinitionKey(string $resourceShortName, array $groups): string
    {
        return $groups ? sprintf('%s-%s', $resourceShortName, implode('_', $groups)) : $resourceShortName;
    }

    /**
     * Gets a definition Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     */
    private function getDefinitionSchema(bool $v3, string $resourceClass, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
    {
        $definitionSchema = new \ArrayObject(['type' => 'object']);

        if (null !== $description = $resourceMetadata->getDescription()) {
            $definitionSchema['description'] = $description;
        }

        if (null !== $iri = $resourceMetadata->getIri()) {
            $definitionSchema['externalDocs'] = ['url' => $iri];
        }

        $options = isset($serializerContext[AbstractNormalizer::GROUPS]) ? ['serializer_groups' => $serializerContext[AbstractNormalizer::GROUPS]] : [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $resourceClass, self::FORMAT, $serializerContext ?? []) : $propertyName;
            if ($propertyMetadata->isRequired()) {
                $definitionSchema['required'][] = $normalizedPropertyName;
            }

            $definitionSchema['properties'][$normalizedPropertyName] = $this->getPropertySchema($v3, $propertyMetadata, $definitions, $serializerContext);
        }

        return $definitionSchema;
    }

    /**
     * Gets a property Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     */
    private function getPropertySchema(bool $v3, PropertyMetadata $propertyMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
    {
        $propertySchema = new \ArrayObject($propertyMetadata->getAttributes()[$v3 ? 'openapi_context' : 'swagger_context'] ?? []);

        if (false === $propertyMetadata->isWritable() && !$propertyMetadata->isInitializable()) {
            $propertySchema['readOnly'] = true;
        }

        if (null !== $description = $propertyMetadata->getDescription()) {
            $propertySchema['description'] = $description;
        }

        if (null === $type = $propertyMetadata->getType()) {
            return $propertySchema;
        }

        $isCollection = $type->isCollection();
        if (null === $valueType = $isCollection ? $type->getCollectionValueType() : $type) {
            $builtinType = 'string';
            $className = null;
        } else {
            $builtinType = $valueType->getBuiltinType();
            $className = $valueType->getClassName();
        }

        $valueSchema = $this->getType($v3, $builtinType, $isCollection, $className, $propertyMetadata->isReadableLink(), $definitions, $serializerContext);

        return new \ArrayObject((array) $propertySchema + $valueSchema);
    }

    /**
     * Gets the Swagger's type corresponding to the given PHP's type.
     */
    private function getType(bool $v3, string $type, bool $isCollection, ?string $className, ?bool $readableLink, \ArrayObject $definitions, array $serializerContext = null): array
    {
        if ($isCollection) {
            return ['type' => 'array', 'items' => $this->getType($v3, $type, false, $className, $readableLink, $definitions, $serializerContext)];
        }

        if (Type::BUILTIN_TYPE_STRING === $type) {
            return ['type' => 'string'];
        }

        if (Type::BUILTIN_TYPE_INT === $type) {
            return ['type' => 'integer'];
        }

        if (Type::BUILTIN_TYPE_FLOAT === $type) {
            return ['type' => 'number'];
        }

        if (Type::BUILTIN_TYPE_BOOL === $type) {
            return ['type' => 'boolean'];
        }

        if (Type::BUILTIN_TYPE_OBJECT === $type) {
            if (null === $className) {
                return ['type' => 'string'];
            }

            if (is_subclass_of($className, \DateTimeInterface::class)) {
                return ['type' => 'string', 'format' => 'date-time'];
            }

            if (!$this->resourceClassResolver->isResourceClass($className)) {
                return ['type' => 'string'];
            }

            if (true === $readableLink) {
                return [
                    '$ref' => sprintf(
                        $v3 ? '#/components/schemas/%s' : '#/definitions/%s',
                        $this->getDefinition($v3, $definitions, $resourceMetadata = $this->resourceMetadataFactory->create($className), $className, $resourceMetadata->getAttribute('output')['class'] ?? $className, $serializerContext)
                    ),
                ];
            }
        }

        return ['type' => 'string'];
    }

    private function computeDoc(bool $v3, Documentation $documentation, \ArrayObject $definitions, \ArrayObject $paths, array $context): array
    {
        $baseUrl = $context[self::BASE_URL] ?? $this->defaultContext[self::BASE_URL];

        if ($v3) {
            $docs = ['openapi' => self::OPENAPI_VERSION];
            if ('/' !== $baseUrl) {
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
            $securityDefinitions['oauth'] = [
                'type' => $this->oauthType,
                'description' => 'OAuth client_credentials Grant',
                'flow' => $this->oauthFlow,
                'tokenUrl' => $this->oauthTokenUrl,
                'authorizationUrl' => $this->oauthAuthorizationUrl,
                'scopes' => $this->oauthScopes,
            ];

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

        if ($v3) {
            if ($securityDefinitions && $security) {
                $docs['security'] = $security;
            }
        } elseif ($securityDefinitions && $security) {
            $docs['securityDefinitions'] = $securityDefinitions;
            $docs['security'] = $security;
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
    private function getFiltersParameters(bool $v3, string $resourceClass, string $operationName, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, array $serializerContext = null): array
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

                $type = $this->getType($v3, $data['type'], $data['is_collection'] ?? false, null, null, $definitions, $serializerContext);
                $v3 ? $parameter['schema'] = $type : $parameter += $type;

                if ('array' === $type['type'] ?? '') {
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
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    private function getSerializerContext(string $operationType, bool $denormalization, ResourceMetadata $resourceMetadata, string $operationName): ?array
    {
        $contextKey = $denormalization ? 'denormalization_context' : 'normalization_context';

        if (OperationType::COLLECTION === $operationType) {
            return $resourceMetadata->getCollectionOperationAttribute($operationName, $contextKey, null, true);
        }

        return $resourceMetadata->getItemOperationAttribute($operationName, $contextKey, null, true);
    }

    private function extractMimeTypes(array $responseFormats): array
    {
        $responseMimeTypes = [];
        foreach ($responseFormats as $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $responseMimeTypes[] = $mimeType;
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
