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

namespace ApiPlatform\Core\Bridge\NelmioApiDoc\Parser;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Extract input and output information for the NelmioApiDocBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @deprecated since version 2.2, to be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
 */
final class ApiPlatformParser implements ParserInterface
{
    public const IN_PREFIX = 'api_platform_in';
    public const OUT_PREFIX = 'api_platform_out';
    public const TYPE_IRI = 'IRI';
    public const TYPE_MAP = [
        Type::BUILTIN_TYPE_BOOL => DataTypes::BOOLEAN,
        Type::BUILTIN_TYPE_FLOAT => DataTypes::FLOAT,
        Type::BUILTIN_TYPE_INT => DataTypes::INTEGER,
        Type::BUILTIN_TYPE_STRING => DataTypes::STRING,
    ];

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $nameConverter;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, NameConverterInterface $nameConverter = null)
    {
        @trigger_error('The '.__CLASS__.' class is deprecated since version 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.', \E_USER_DEPRECATED);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $item)
    {
        $data = explode(':', $item['class'], 3);
        if (!\in_array($data[0], [self::IN_PREFIX, self::OUT_PREFIX], true)) {
            return false;
        }
        if (!isset($data[1])) {
            return false;
        }

        try {
            $this->resourceMetadataFactory->create($data[1]);

            return true;
        } catch (ResourceClassNotFoundException $e) {
            // return false
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $item): array
    {
        [$io, $resourceClass, $operationName] = explode(':', $item['class'], 3);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $classOperations = $this->getGroupsForItemAndCollectionOperation($resourceMetadata, $operationName, $io);

        if (!empty($classOperations['serializer_groups'])) {
            return $this->getPropertyMetadata($resourceMetadata, $resourceClass, $io, [], $classOperations);
        }

        return $this->parseResource($resourceMetadata, $resourceClass, $io);
    }

    /**
     * Parses a class.
     *
     * @param string[] $visited
     */
    private function parseResource(ResourceMetadata $resourceMetadata, string $resourceClass, string $io, array $visited = []): array
    {
        $visited[] = $resourceClass;

        $options = [];
        $attributes = $resourceMetadata->getAttributes();

        if (isset($attributes['normalization_context'][AbstractNormalizer::GROUPS])) {
            $options['serializer_groups'] = (array) $attributes['normalization_context'][AbstractNormalizer::GROUPS];
        }

        if (isset($attributes['denormalization_context'][AbstractNormalizer::GROUPS])) {
            if (isset($options['serializer_groups'])) {
                $options['serializer_groups'] += $attributes['denormalization_context'][AbstractNormalizer::GROUPS];
            } else {
                $options['serializer_groups'] = (array) $attributes['denormalization_context'][AbstractNormalizer::GROUPS];
            }
        }

        return $this->getPropertyMetadata($resourceMetadata, $resourceClass, $io, $visited, $options);
    }

    private function getGroupsContext(ResourceMetadata $resourceMetadata, string $operationName, bool $isNormalization): array
    {
        $groupsContext = $isNormalization ? 'normalization_context' : 'denormalization_context';
        $itemOperationAttribute = $resourceMetadata->getItemOperationAttribute($operationName, $groupsContext, [AbstractNormalizer::GROUPS => []], true)[AbstractNormalizer::GROUPS];
        $collectionOperationAttribute = $resourceMetadata->getCollectionOperationAttribute($operationName, $groupsContext, [AbstractNormalizer::GROUPS => []], true)[AbstractNormalizer::GROUPS];

        return [
            $groupsContext => [
                AbstractNormalizer::GROUPS => array_merge((array) ($itemOperationAttribute ?? []), (array) ($collectionOperationAttribute ?? [])),
            ],
        ];
    }

    /**
     * Returns groups of item & collection.
     */
    private function getGroupsForItemAndCollectionOperation(ResourceMetadata $resourceMetadata, string $operationName, string $io): array
    {
        $operation = $this->getGroupsContext($resourceMetadata, $operationName, true);
        $operation += $this->getGroupsContext($resourceMetadata, $operationName, false);

        if (self::OUT_PREFIX === $io) {
            return [
                'serializer_groups' => !empty($operation['normalization_context']) ? $operation['normalization_context'][AbstractNormalizer::GROUPS] : [],
            ];
        }

        if (self::IN_PREFIX === $io) {
            return [
                'serializer_groups' => !empty($operation['denormalization_context']) ? $operation['denormalization_context'][AbstractNormalizer::GROUPS] : [],
            ];
        }

        return [];
    }

    /**
     * Returns a property metadata.
     *
     * @param string[] $visited
     * @param string[] $options
     */
    private function getPropertyMetadata(ResourceMetadata $resourceMetadata, string $resourceClass, string $io, array $visited, array $options): array
    {
        $data = [];

        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            if (
                ($propertyMetadata->isReadable() && self::OUT_PREFIX === $io) ||
                ($propertyMetadata->isWritable() && self::IN_PREFIX === $io)
            ) {
                $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $resourceClass) : $propertyName;
                $data[$normalizedPropertyName] = $this->parseProperty($resourceMetadata, $propertyMetadata, $io, null, $visited);
            }
        }

        return $data;
    }

    /**
     * Parses a property.
     *
     * @param string   $io
     * @param string[] $visited
     */
    private function parseProperty(ResourceMetadata $resourceMetadata, PropertyMetadata $propertyMetadata, $io, Type $type = null, array $visited = []): array
    {
        $data = [
            'dataType' => null,
            'required' => $propertyMetadata->isRequired(),
            'description' => $propertyMetadata->getDescription(),
            'readonly' => !$propertyMetadata->isWritable(),
        ];

        if (null === $type && null === $type = $propertyMetadata->getType()) {
            // Default to string
            $data['dataType'] = DataTypes::STRING;

            return $data;
        }

        if ($type->isCollection()) {
            $data['actualType'] = DataTypes::COLLECTION;

            if ($collectionType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()) {
                $subProperty = $this->parseProperty($resourceMetadata, $propertyMetadata, $io, $collectionType, $visited);
                if (self::TYPE_IRI === $subProperty['dataType']) {
                    $data['dataType'] = 'array of IRIs';
                    $data['subType'] = DataTypes::STRING;

                    return $data;
                }

                $data['subType'] = $subProperty['subType'] ?? null;
                if (isset($subProperty['children'])) {
                    $data['children'] = $subProperty['children'];
                }
            }

            return $data;
        }

        $builtinType = $type->getBuiltinType();
        if ('object' === $builtinType) {
            $className = $type->getClassName();

            if (is_a($className, \DateTimeInterface::class, true)) {
                $data['dataType'] = DataTypes::DATETIME;
                $data['format'] = sprintf('{DateTime %s}', \DateTime::RFC3339);

                return $data;
            }

            try {
                $this->resourceMetadataFactory->create($className);
            } catch (ResourceClassNotFoundException $e) {
                $data['actualType'] = DataTypes::MODEL;
                $data['subType'] = $className;

                return $data;
            }

            if (
                (self::OUT_PREFIX === $io && true !== $propertyMetadata->isReadableLink()) ||
                (self::IN_PREFIX === $io && true !== $propertyMetadata->isWritableLink())
            ) {
                $data['dataType'] = self::TYPE_IRI;
                $data['actualType'] = DataTypes::STRING;

                return $data;
            }

            $data['actualType'] = DataTypes::MODEL;
            $data['subType'] = $className;
            $data['children'] = \in_array($className, $visited, true) ? [] : $this->parseResource($resourceMetadata, $className, $io, $visited);

            return $data;
        }

        $data['dataType'] = self::TYPE_MAP[$builtinType] ?? DataTypes::STRING;

        return $data;
    }
}
