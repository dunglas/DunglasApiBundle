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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Generic item normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizer extends AbstractItemNormalizer
{
    private $logger;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, ItemDataProviderInterface $itemDataProvider = null, bool $allowPlainIdentifiers = false, LoggerInterface $logger = null, iterable $dataTransformers = [], $resourceMetadataFactory = null, ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $itemDataProvider, $allowPlainIdentifiers, [], $dataTransformers, $resourceMetadataFactory, $resourceAccessChecker);

        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            if (isset($context['resource_class'])) {
                $this->updateObjectToPopulate($data, $context);
            } else {
                // See https://github.com/api-platform/core/pull/2326 to understand this message.
                $this->logger->warning('The "resource_class" key is missing from the context.', [
                    'context' => $context,
                ]);
            }
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    private function updateObjectToPopulate(array $data, array &$context): void
    {
        try {
            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri((string) $data['id'], $context + ['fetch_data' => true]);
        } catch (InvalidArgumentException $e) {
            if ($this->iriConverter instanceof IriConverterInterface) {
                $operation = $this->resourceMetadataFactory->create($context['resource_class'])->getOperation();
                $iri = $this->iriConverter->getIriFromResourceClass($context['resource_class'], $operation->getName(), UrlGeneratorInterface::ABS_PATH, ['identifiers_values' => ['id' => $data['id']]]);
            } else {
                // remove in 3.0
                $identifier = null;
                $options = $this->getFactoryOptions($context);

                foreach ($this->propertyNameCollectionFactory->create($context['resource_class'], $options) as $propertyName) {
                    if (true === $this->propertyMetadataFactory->create($context['resource_class'], $propertyName)->isIdentifier()) {
                        $identifier = $propertyName;
                        break;
                    }
                }

                if (null === $identifier) {
                    throw $e;
                }
                $iri = sprintf('%s/%s', $this->iriConverter->getIriFromResourceClass($context['resource_class']), $data[$identifier]);
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri($iri, ['fetch_data' => true]);
        }
    }
}
