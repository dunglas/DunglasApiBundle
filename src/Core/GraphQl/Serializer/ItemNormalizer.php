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

namespace ApiPlatform\Core\GraphQl\Serializer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Serializer\ItemNormalizer as BaseItemNormalizer;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * GraphQL normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends BaseItemNormalizer
{
    use ClassInfoTrait;

    public const FORMAT = 'graphql';
    public const ITEM_RESOURCE_CLASS_KEY = '#itemResourceClass';
    public const ITEM_IDENTIFIERS_KEY = '#itemIdentifiers';

    private $identifiersExtractor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, IdentifiersExtractorInterface $identifiersExtractor, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, ItemDataProviderInterface $itemDataProvider = null, bool $allowPlainIdentifiers = false, LoggerInterface $logger = null, iterable $dataTransformers = [], ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $itemDataProvider, $allowPlainIdentifiers, $logger ?: new NullLogger(), $dataTransformers, $resourceMetadataFactory, $resourceAccessChecker);

        $this->identifiersExtractor = $identifiersExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (null !== $this->getOutputClass($this->getObjectClass($object), $context)) {
            return parent::normalize($object, $format, $context);
        }

        $data = parent::normalize($object, $format, $context);
        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array.');
        }

        if (!($context['no_resolver_data'] ?? false)) {
            $data[self::ITEM_RESOURCE_CLASS_KEY] = $this->getObjectClass($object);
            $data[self::ITEM_IDENTIFIERS_KEY] = $this->identifiersExtractor->getIdentifiersFromItem($object);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeCollectionOfRelations(PropertyMetadata $propertyMetadata, $attributeValue, string $resourceClass, ?string $format, array $context): array
    {
        // to-many are handled directly by the GraphQL resolver
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
    {
        $allowedAttributes = parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);

        if (($context['api_denormalize'] ?? false) && \is_array($allowedAttributes) && false !== ($indexId = array_search('id', $allowedAttributes, true))) {
            $allowedAttributes[] = '_id';
            array_splice($allowedAttributes, (int) $indexId, 1);
        }

        return $allowedAttributes;
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = []): void
    {
        if ('_id' === $attribute) {
            $attribute = 'id';
        }

        parent::setAttributeValue($object, $attribute, $value, $format, $context);
    }
}
