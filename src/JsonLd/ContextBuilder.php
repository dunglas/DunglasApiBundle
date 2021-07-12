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

namespace ApiPlatform\Core\JsonLd;

use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\ResourceClassResolver;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements AnonymousContextBuilderInterface
{
    use ClassInfoTrait;

    public const FORMAT = 'jsonld';

    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $urlGenerator;

    /**
     * @var NameConverterInterface|null
     */
    private $nameConverter;
    private $identifiersExtractor;
    private $propertyAccessor;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, UrlGeneratorInterface $urlGenerator, NameConverterInterface $nameConverter = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->urlGenerator = $urlGenerator;
        $this->nameConverter = $nameConverter;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, new ResourceClassResolver($resourceNameCollectionFactory));
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseContext(int $referenceType = UrlGeneratorInterface::ABS_URL): array
    {
        return [
            '@vocab' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT], UrlGeneratorInterface::ABS_URL).'#',
            'hydra' => self::HYDRA_NS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntrypointContext(int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $context = $this->getBaseContext($referenceType);

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $resourceName = lcfirst($resourceMetadata->getShortName());

            $context[$resourceName] = [
                '@id' => 'Entrypoint/'.$resourceName,
                '@type' => '@id',
            ];
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContext(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (null === $shortName = $resourceMetadata->getShortName()) {
            return [];
        }

        if ($resourceMetadata->getAttribute('normalization_context')['iri_only'] ?? false) {
            $context = $this->getBaseContext($referenceType);
            $context['hydra:member']['@type'] = '@id';

            return $context;
        }

        return $this->getResourceContextWithShortname($resourceClass, $referenceType, $shortName);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContextUri(string $resourceClass, int $referenceType = null): string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (null === $referenceType) {
            $referenceType = $resourceMetadata->getAttribute('url_generation_strategy');
        }

        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $resourceMetadata->getShortName()], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnonymousResourceContext($object, array $context = [], int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $outputClass = $this->getObjectClass($object);
        $shortName = (new \ReflectionClass($outputClass))->getShortName();

        $jsonLdContext = [
            '@context' => $this->getResourceContextWithShortname(
                $outputClass,
                $referenceType,
                $shortName
            ),
            '@type' => $shortName,
            '@id' => $this->getAnonymousResourceIdentifier($object, $outputClass, $context),
        ];

        if ($context['has_context'] ?? false) {
            unset($jsonLdContext['@context']);
        }

        // here the object can be different from the resource given by the $context['api_resource'] value
        if (isset($context['api_resource'])) {
            $jsonLdContext['@type'] = $this->resourceMetadataFactory->create($this->getObjectClass($context['api_resource']))->getShortName();
        }

        return $jsonLdContext;
    }

    private function getAnonymousResourceIdentifier($object, $class, array $context = [])
    {
        if (isset($context['iri'])) {
            return $context['iri'];
        }

        // Non-resource may have no identifiers in which case we will generate one
        try {
            $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($class);
            $values = [];
            foreach ($identifiers as $identifier) {
                if (!is_scalar($value = $this->propertyAccessor->getValue($object, $identifier))) {
                    throw new RuntimeException('Anonymous identifiers value must be scalar.');
                }

                $values[$identifier] = $value;
            }

            return '_:'.(\count($values) > 1 ? CompositeIdentifierParser::stringify($values) : current($values));
        } catch (RuntimeException $e) {
            return '_:'.(\function_exists('spl_object_id') ? spl_object_id($object) : spl_object_hash($object));
        }
    }

    private function getResourceContextWithShortname(string $resourceClass, int $referenceType, string $shortName): array
    {
        $context = $this->getBaseContext($referenceType);

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            if ($propertyMetadata->isIdentifier() && true !== $propertyMetadata->isWritable()) {
                continue;
            }

            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $resourceClass, self::FORMAT) : $propertyName;
            $jsonldContext = $propertyMetadata->getAttributes()['jsonld_context'] ?? [];

            if (!$id = $propertyMetadata->getIri()) {
                $id = sprintf('%s/%s', $shortName, $convertedName);
            }

            if (false === $propertyMetadata->isReadableLink()) {
                $jsonldContext += [
                    '@id' => $id,
                    '@type' => '@id',
                ];
            }

            if (empty($jsonldContext)) {
                $context[$convertedName] = $id;
            } else {
                $context[$convertedName] = $jsonldContext + [
                    '@id' => $id,
                ];
            }
        }

        return $context;
    }
}
