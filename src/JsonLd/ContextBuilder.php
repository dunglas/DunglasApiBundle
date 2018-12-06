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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements ContextBuilderInterface
{
    const FORMAT = 'jsonld';

    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $urlGenerator;

    /**
     * @var NameConverterInterface|null
     */
    private $nameConverter;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, UrlGeneratorInterface $urlGenerator, NameConverterInterface $nameConverter = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->urlGenerator = $urlGenerator;
        $this->nameConverter = $nameConverter;
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
        $context = $this->getBaseContext($referenceType);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $shortName = $resourceMetadata->getShortName();
        $context += $resourceMetadata->getAttributes()['jsonld_context'] ?? [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            if ($propertyMetadata->isIdentifier() && true !== $propertyMetadata->isWritable()) {
                continue;
            }

            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($propertyName) : $propertyName;
            $propertyJsonldContext = $propertyMetadata->getAttributes()['jsonld_context'] ?? [];

            if (!$id = $propertyMetadata->getIri()) {
                $id = sprintf('%s/%s', $shortName, $convertedName);
            }

            if (true !== $propertyMetadata->isReadableLink()) {
                $propertyJsonldContext += [
                    '@id' => $id,
                    '@type' => '@id',
                ];
            }

            if (empty($propertyJsonldContext)) {
                $context[$convertedName] = $id;
            } else {
                $context[$convertedName] = $propertyJsonldContext + [
                    '@id' => $id,
                ];
            }
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContextUri(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $resourceMetadata->getShortName()], $referenceType);
    }
}
