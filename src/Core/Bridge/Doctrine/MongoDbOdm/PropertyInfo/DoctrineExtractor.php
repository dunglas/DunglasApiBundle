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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyInfo;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDbClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Extracts data using Doctrine MongoDB ODM metadata.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DoctrineExtractor implements PropertyListExtractorInterface, PropertyTypeExtractorInterface, PropertyAccessExtractorInterface
{
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = [])
    {
        if (null === $metadata = $this->getMetadata($class)) {
            return null;
        }

        return $metadata->getFieldNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = [])
    {
        if (null === $metadata = $this->getMetadata($class)) {
            return null;
        }

        if ($metadata->hasAssociation($property)) {
            $class = $metadata->getAssociationTargetClass($property);

            if ($metadata->isSingleValuedAssociation($property)) {
                $nullable = $metadata instanceof MongoDbClassMetadata && $metadata->isNullable($property);

                return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, $class)];
            }

            $collectionKeyType = Type::BUILTIN_TYPE_INT;

            return [
                new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    Collection::class,
                    true,
                    new Type($collectionKeyType),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, $class)
                ),
            ];
        }

        if ($metadata->hasField($property)) {
            $typeOfField = $metadata->getTypeOfField($property);
            $nullable = $metadata instanceof MongoDbClassMetadata && $metadata->isNullable($property);

            switch ($typeOfField) {
                case MongoDbType::DATE:
                    return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, 'DateTime')];
                case MongoDbType::DATE_IMMUTABLE:
                    return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, 'DateTimeImmutable')];
                case MongoDbType::HASH:
                    return [new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true)];
                case MongoDbType::COLLECTION:
                    return [new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true, new Type(Type::BUILTIN_TYPE_INT))];
                default:
                    $builtinType = $this->getPhpType($typeOfField);

                    return $builtinType ? [new Type($builtinType, $nullable)] : null;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($class, $property, array $context = []): ?bool
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($class, $property, array $context = []): ?bool
    {
        if (
            null === ($metadata = $this->getMetadata($class))
            || $metadata instanceof MongoDbClassMetadata && MongoDbClassMetadata::GENERATOR_TYPE_NONE === $metadata->generatorType
            || !\in_array($property, $metadata->getIdentifierFieldNames(), true)
        ) {
            return null;
        }

        return false;
    }

    private function getMetadata(string $class): ?ClassMetadata
    {
        try {
            return $this->objectManager->getClassMetadata($class);
        } catch (MappingException $exception) {
            return null;
        }
    }

    /**
     * Gets the corresponding built-in PHP type.
     */
    private function getPhpType(string $doctrineType): ?string
    {
        switch ($doctrineType) {
            case MongoDbType::INTEGER:
            case MongoDbType::INT:
            case MongoDbType::INTID:
            case MongoDbType::KEY:
                return Type::BUILTIN_TYPE_INT;
            case MongoDbType::FLOAT:
                return Type::BUILTIN_TYPE_FLOAT;
            case MongoDbType::STRING:
            case MongoDbType::ID:
            case MongoDbType::OBJECTID:
            case MongoDbType::TIMESTAMP:
            case MongoDbType::BINDATA:
            case MongoDbType::BINDATABYTEARRAY:
            case MongoDbType::BINDATACUSTOM:
            case MongoDbType::BINDATAFUNC:
            case MongoDbType::BINDATAMD5:
            case MongoDbType::BINDATAUUID:
            case MongoDbType::BINDATAUUIDRFC4122:
                return Type::BUILTIN_TYPE_STRING;
            case MongoDbType::BOOLEAN:
            case MongoDbType::BOOL:
                return Type::BUILTIN_TYPE_BOOL;
        }

        return null;
    }
}
