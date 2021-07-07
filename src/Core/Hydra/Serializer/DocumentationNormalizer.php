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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceToResourceMetadataTrait;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use ResourceToResourceMetadataTrait;
    public const FORMAT = 'jsonld';

    /**
     * @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface
     */
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceClassResolver;
    private $operationMethodResolver;
    private $urlGenerator;
    private $subresourceOperationFactory;
    private $nameConverter;

    public function __construct($resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, OperationMethodResolverInterface $operationMethodResolver = null, UrlGeneratorInterface $urlGenerator, SubresourceOperationFactoryInterface $subresourceOperationFactory = null, NameConverterInterface $nameConverter = null)
    {
        if ($operationMethodResolver) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.5 and will be removed in 3.0.', OperationMethodResolverInterface::class, __METHOD__), \E_USER_DEPRECATED);
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($resourceMetadataFactory && $resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be replaced by %s in 3.0.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class), \E_USER_DEPRECATED);
        }

        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->urlGenerator = $urlGenerator;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $classes = [];
        $entrypointProperties = [];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                $resourceMetadata = $this->transformResourceToResourceMetadata($resourceMetadata[0]);
            }

            $shortName = $resourceMetadata->getShortName();
            $prefixedShortName = $resourceMetadata->getIri() ?? "#$shortName";

            $this->populateEntrypointProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $entrypointProperties);
            $classes[] = $this->getClass($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context);
        }

        return $this->computeDoc($object, $this->getClasses($entrypointProperties, $classes));
    }

    /**
     * Populates entrypoint properties.
     */
    private function populateEntrypointProperties(string $resourceClass, ResourceMetadata $resourceMetadata, string $shortName, string $prefixedShortName, array &$entrypointProperties)
    {
        $hydraCollectionOperations = $this->getHydraOperations($resourceClass, $resourceMetadata, $prefixedShortName, true);
        if (empty($hydraCollectionOperations)) {
            return;
        }

        $entrypointProperty = [
            '@type' => 'hydra:SupportedProperty',
            'hydra:property' => [
                '@id' => sprintf('#Entrypoint/%s', lcfirst($shortName)),
                '@type' => 'hydra:Link',
                'domain' => '#Entrypoint',
                'rdfs:label' => "The collection of $shortName resources",
                'rdfs:range' => [
                    ['@id' => 'hydra:Collection'],
                    [
                        'owl:equivalentClass' => [
                            'owl:onProperty' => ['@id' => 'hydra:member'],
                            'owl:allValuesFrom' => ['@id' => $prefixedShortName],
                        ],
                    ],
                ],
                'hydra:supportedOperation' => $hydraCollectionOperations,
            ],
            'hydra:title' => "The collection of $shortName resources",
            'hydra:readable' => true,
            'hydra:writeable' => false,
        ];

        if ($resourceMetadata->getCollectionOperationAttribute('GET', 'deprecation_reason', null, true)) {
            $entrypointProperty['owl:deprecated'] = true;
        }

        $entrypointProperties[] = $entrypointProperty;
    }

    /**
     * Gets a Hydra class.
     */
    private function getClass(string $resourceClass, ResourceMetadata $resourceMetadata, string $shortName, string $prefixedShortName, array $context): array
    {
        $class = [
            '@id' => $prefixedShortName,
            '@type' => 'hydra:Class',
            'rdfs:label' => $shortName,
            'hydra:title' => $shortName,
            'hydra:supportedProperty' => $this->getHydraProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context),
            'hydra:supportedOperation' => $this->getHydraOperations($resourceClass, $resourceMetadata, $prefixedShortName, false),
        ];

        if (null !== $description = $resourceMetadata->getDescription()) {
            $class['hydra:description'] = $description;
        }

        if ($resourceMetadata->getAttribute('deprecation_reason')) {
            $class['owl:deprecated'] = true;
        }

        return $class;
    }

    /**
     * Gets the context for the property name factory.
     */
    private function getPropertyNameCollectionFactoryContext(ResourceMetadata $resourceMetadata): array
    {
        $attributes = $resourceMetadata->getAttributes();
        $context = [];

        if (isset($attributes['normalization_context'][AbstractNormalizer::GROUPS])) {
            $context['serializer_groups'] = (array) $attributes['normalization_context'][AbstractNormalizer::GROUPS];
        }

        if (!isset($attributes['denormalization_context'][AbstractNormalizer::GROUPS])) {
            return $context;
        }

        if (isset($context['serializer_groups'])) {
            foreach ((array) $attributes['denormalization_context'][AbstractNormalizer::GROUPS] as $groupName) {
                $context['serializer_groups'][] = $groupName;
            }

            return $context;
        }

        $context['serializer_groups'] = (array) $attributes['denormalization_context'][AbstractNormalizer::GROUPS];

        return $context;
    }

    /**
     * Gets Hydra properties.
     */
    private function getHydraProperties(string $resourceClass, ResourceMetadata $resourceMetadata, string $shortName, string $prefixedShortName, array $context): array
    {
        $classes = [];
        foreach ($resourceMetadata->getCollectionOperations() as $operationName => $operation) {
            $inputMetadata = $resourceMetadata->getTypedOperationAttribute(OperationType::COLLECTION, $operationName, 'input', ['class' => $resourceClass], true);
            if (null !== $inputClass = $inputMetadata['class'] ?? null) {
                $classes[$inputClass] = true;
            }

            $outputMetadata = $resourceMetadata->getTypedOperationAttribute(OperationType::COLLECTION, $operationName, 'output', ['class' => $resourceClass], true);
            if (null !== $outputClass = $outputMetadata['class'] ?? null) {
                $classes[$outputClass] = true;
            }
        }

        /** @var string[] $classes */
        $classes = array_keys($classes);
        $properties = [];
        foreach ($classes as $class) {
            foreach ($this->propertyNameCollectionFactory->create($class, $this->getPropertyNameCollectionFactoryContext($resourceMetadata)) as $propertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($class, $propertyName);
                if (true === $propertyMetadata->isIdentifier() && false === $propertyMetadata->isWritable()) {
                    continue;
                }

                if ($this->nameConverter) {
                    $propertyName = $this->nameConverter->normalize($propertyName, $class, self::FORMAT, $context);
                }

                $properties[] = $this->getProperty($propertyMetadata, $propertyName, $prefixedShortName, $shortName);
            }
        }

        return $properties;
    }

    /**
     * Gets Hydra operations.
     */
    private function getHydraOperations(string $resourceClass, ResourceMetadata $resourceMetadata, string $prefixedShortName, bool $collection): array
    {
        if (null === $operations = $collection ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return [];
        }

        $hydraOperations = [];
        foreach ($operations as $operationName => $operation) {
            $hydraOperations[] = $this->getHydraOperation($resourceClass, $resourceMetadata, $operationName, $operation, $prefixedShortName, $collection ? OperationType::COLLECTION : OperationType::ITEM);
        }

        if (null !== $this->subresourceOperationFactory) {
            foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $operation) {
                $subresourceMetadata = $this->resourceMetadataFactory->create($operation['resource_class']);
                if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                    $subresourceMetadata = $this->transformResourceToResourceMetadata($subresourceMetadata[0]);
                }
                $propertyMetadata = $this->propertyMetadataFactory->create(end($operation['identifiers'])[0], $operation['property']);
                $hydraOperations[] = $this->getHydraOperation($resourceClass, $subresourceMetadata, $operation['route_name'], $operation, "#{$subresourceMetadata->getShortName()}", OperationType::SUBRESOURCE, $propertyMetadata->getSubresource());
            }
        }

        return $hydraOperations;
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     *
     * @param SubresourceMetadata $subresourceMetadata
     */
    private function getHydraOperation(string $resourceClass, ResourceMetadata $resourceMetadata, string $operationName, array $operation, string $prefixedShortName, string $operationType, SubresourceMetadata $subresourceMetadata = null): array
    {
        if ($this->operationMethodResolver) {
            if (OperationType::COLLECTION === $operationType) {
                $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
            } elseif (OperationType::ITEM === $operationType) {
                $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
            } else {
                $method = 'GET';
            }
        } else {
            $method = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'method', 'GET');
        }

        $hydraOperation = $operation['hydra_context'] ?? [];
        if ($resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'deprecation_reason', null, true)) {
            $hydraOperation['owl:deprecated'] = true;
        }

        $shortName = $resourceMetadata->getShortName();
        $inputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'input', ['class' => false]);
        $inputClass = \array_key_exists('class', $inputMetadata) ? $inputMetadata['class'] : false;
        $outputMetadata = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'output', ['class' => false]);
        $outputClass = \array_key_exists('class', $outputMetadata) ? $outputMetadata['class'] : false;

        if ('GET' === $method && OperationType::COLLECTION === $operationType) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:title' => "Retrieves the collection of $shortName resources.",
                'returns' => 'hydra:Collection',
            ];
        } elseif ('GET' === $method && OperationType::SUBRESOURCE === $operationType) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:title' => $subresourceMetadata && $subresourceMetadata->isCollection() ? "Retrieves the collection of $shortName resources." : "Retrieves a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : "#$shortName",
            ];
        } elseif ('GET' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:FindAction'],
                'hydra:title' => "Retrieves $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PATCH' === $method) {
            $hydraOperation += [
                '@type' => 'hydra:Operation',
                'hydra:title' => "Updates the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('POST' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:CreateAction'],
                'hydra:title' => "Creates a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PUT' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:ReplaceAction'],
                'hydra:title' => "Replaces the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('DELETE' === $method) {
            $hydraOperation += [
                '@type' => ['hydra:Operation', 'schema:DeleteAction'],
                'hydra:title' => "Deletes the $shortName resource.",
                'returns' => 'owl:Nothing',
            ];
        }

        $hydraOperation['hydra:method'] ?? $hydraOperation['hydra:method'] = $method;

        if (!isset($hydraOperation['rdfs:label']) && isset($hydraOperation['hydra:title'])) {
            $hydraOperation['rdfs:label'] = $hydraOperation['hydra:title'];
        }

        ksort($hydraOperation);

        return $hydraOperation;
    }

    /**
     * Gets the range of the property.
     */
    private function getRange(PropertyMetadata $propertyMetadata): ?string
    {
        $jsonldContext = $propertyMetadata->getAttributes()['jsonld_context'] ?? [];

        if (isset($jsonldContext['@type'])) {
            return $jsonldContext['@type'];
        }

        if (null === $type = $propertyMetadata->getType()) {
            return null;
        }

        if ($type->isCollection() && null !== $collectionType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()) {
            $type = $collectionType;
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_STRING:
                return 'xmls:string';
            case Type::BUILTIN_TYPE_INT:
                return 'xmls:integer';
            case Type::BUILTIN_TYPE_FLOAT:
                return 'xmls:decimal';
            case Type::BUILTIN_TYPE_BOOL:
                return 'xmls:boolean';
            case Type::BUILTIN_TYPE_OBJECT:
                if (null === $className = $type->getClassName()) {
                    return null;
                }

                if (is_a($className, \DateTimeInterface::class, true)) {
                    return 'xmls:dateTime';
                }

                if ($this->resourceClassResolver->isResourceClass($className)) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($className);
                    if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                        $resourceMetadata = $this->transformResourceToResourceMetadata($resourceMetadata[0]);
                    }

                    return $resourceMetadata->getIri() ?? "#{$resourceMetadata->getShortName()}";
                }
                break;
        }

        return null;
    }

    /**
     * Builds the classes array.
     */
    private function getClasses(array $entrypointProperties, array $classes): array
    {
        $classes[] = [
            '@id' => '#Entrypoint',
            '@type' => 'hydra:Class',
            'hydra:title' => 'The API entrypoint',
            'hydra:supportedProperty' => $entrypointProperties,
            'hydra:supportedOperation' => [
                '@type' => 'hydra:Operation',
                'hydra:method' => 'GET',
                'rdfs:label' => 'The API entrypoint.',
                'returns' => '#EntryPoint',
            ],
        ];

        // Constraint violation
        $classes[] = [
            '@id' => '#ConstraintViolation',
            '@type' => 'hydra:Class',
            'hydra:title' => 'A constraint violation',
            'hydra:supportedProperty' => [
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => '#ConstraintViolation/propertyPath',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'propertyPath',
                        'domain' => '#ConstraintViolation',
                        'range' => 'xmls:string',
                    ],
                    'hydra:title' => 'propertyPath',
                    'hydra:description' => 'The property path of the violation',
                    'hydra:readable' => true,
                    'hydra:writeable' => false,
                ],
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => '#ConstraintViolation/message',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'message',
                        'domain' => '#ConstraintViolation',
                        'range' => 'xmls:string',
                    ],
                    'hydra:title' => 'message',
                    'hydra:description' => 'The message associated with the violation',
                    'hydra:readable' => true,
                    'hydra:writeable' => false,
                ],
            ],
        ];

        // Constraint violation list
        $classes[] = [
            '@id' => '#ConstraintViolationList',
            '@type' => 'hydra:Class',
            'subClassOf' => 'hydra:Error',
            'hydra:title' => 'A constraint violation list',
            'hydra:supportedProperty' => [
                [
                    '@type' => 'hydra:SupportedProperty',
                    'hydra:property' => [
                        '@id' => '#ConstraintViolationList/violations',
                        '@type' => 'rdf:Property',
                        'rdfs:label' => 'violations',
                        'domain' => '#ConstraintViolationList',
                        'range' => '#ConstraintViolation',
                    ],
                    'hydra:title' => 'violations',
                    'hydra:description' => 'The violations',
                    'hydra:readable' => true,
                    'hydra:writeable' => false,
                ],
            ],
        ];

        return $classes;
    }

    /**
     * Gets a property definition.
     */
    private function getProperty(PropertyMetadata $propertyMetadata, string $propertyName, string $prefixedShortName, string $shortName): array
    {
        $propertyData = [
            '@id' => $propertyMetadata->getIri() ?? "#$shortName/$propertyName",
            '@type' => false === $propertyMetadata->isReadableLink() ? 'hydra:Link' : 'rdf:Property',
            'rdfs:label' => $propertyName,
            'domain' => $prefixedShortName,
        ];

        $type = $propertyMetadata->getType();

        if (null !== $type && !$type->isCollection() && (null !== $className = $type->getClassName()) && $this->resourceClassResolver->isResourceClass($className)) {
            $propertyData['owl:maxCardinality'] = 1;
        }

        $property = [
            '@type' => 'hydra:SupportedProperty',
            'hydra:property' => $propertyData,
            'hydra:title' => $propertyName,
            'hydra:required' => $propertyMetadata->isRequired(),
            'hydra:readable' => $propertyMetadata->isReadable(),
            'hydra:writeable' => $propertyMetadata->isWritable() || $propertyMetadata->isInitializable(),
        ];

        if (null !== $range = $this->getRange($propertyMetadata)) {
            $property['hydra:property']['range'] = $range;
        }

        if (null !== $description = $propertyMetadata->getDescription()) {
            $property['hydra:description'] = $description;
        }

        if ($propertyMetadata->getAttribute('deprecation_reason')) {
            $property['owl:deprecated'] = true;
        }

        return $property;
    }

    /**
     * Computes the documentation.
     */
    private function computeDoc(Documentation $object, array $classes): array
    {
        $doc = ['@context' => $this->getContext(), '@id' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT]), '@type' => 'hydra:ApiDocumentation'];

        if ('' !== $object->getTitle()) {
            $doc['hydra:title'] = $object->getTitle();
        }

        if ('' !== $object->getDescription()) {
            $doc['hydra:description'] = $object->getDescription();
        }

        $doc['hydra:entrypoint'] = $this->urlGenerator->generate('api_entrypoint');
        $doc['hydra:supportedClass'] = $classes;

        return $doc;
    }

    /**
     * Builds the JSON-LD context for the API documentation.
     */
    private function getContext(): array
    {
        return [
            '@vocab' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT], UrlGeneratorInterface::ABS_URL).'#',
            'hydra' => ContextBuilderInterface::HYDRA_NS,
            'rdf' => ContextBuilderInterface::RDF_NS,
            'rdfs' => ContextBuilderInterface::RDFS_NS,
            'xmls' => ContextBuilderInterface::XML_NS,
            'owl' => ContextBuilderInterface::OWL_NS,
            'schema' => ContextBuilderInterface::SCHEMA_ORG_NS,
            'domain' => ['@id' => 'rdfs:domain', '@type' => '@id'],
            'range' => ['@id' => 'rdfs:range', '@type' => '@id'],
            'subClassOf' => ['@id' => 'rdfs:subClassOf', '@type' => '@id'],
            'expects' => ['@id' => 'hydra:expects', '@type' => '@id'],
            'returns' => ['@id' => 'hydra:returns', '@type' => '@id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
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
}
