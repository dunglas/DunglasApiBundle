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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * BC layer with the < 3.0 ResourceMetadata system.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ResourceCollectionMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;
    private $resourceCollectionMetadataFactory;
    private $nameConverter;

    public function __construct(ResourceMetadataFactoryInterface $decorated, ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
        $this->nameConverter = new CamelCaseToSnakeCaseNameConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        try {
            return $this->decorated->create($resourceClass);
        } catch (ResourceClassNotFoundException $e) {
            $resourceMetadataCollection = $this->resourceCollectionMetadataFactory->create($resourceClass);
            if (!isset($resourceMetadataCollection[0])) {
                throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
            }

            @trigger_error(sprintf('Using a %s for a #[Resource] is deprecated since 2.7 and will not be possible in 3.0. Use %s instead.', ResourceMetadataFactoryInterface::class, ResourceCollectionMetadataFactoryInterface::class), \E_USER_DEPRECATED);

            $collectionOperations = [];
            $itemOperations = [];
            foreach ($resourceMetadataCollection[0]->operations as $name => $operation) {
                if (!$operation->identifiers) {
                    $collectionOperations[$name] = $this->toArray($operation);
                    continue;
                }

                $itemOperations[$name] = $this->toArray($operation);
            }
            $attributes = $this->toArray($resourceMetadataCollection[0]);
            $graphql = $resourceMetadataCollection[0]->graphQl ? $this->toArray($resourceMetadataCollection[0]->graphQl) : null;

            return new ResourceMetadata($resourceMetadataCollection[0]->shortName, $resourceMetadataCollection[0]->description, null, $itemOperations, $collectionOperations, $attributes, null, $graphql);
        }
    }

    private function toArray($object): array
    {
        $arr = [];
        foreach ($object as $key => $value) {
            $arr[$this->nameConverter->normalize($key)] = $value;
        }

        return $arr;
    }
}
