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

namespace ApiPlatform\Core\Tests\Fixtures\Metadata\Resource;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * A fixture resource metadata factory which allows developers to set up
 * a mapping to return $metadata by $class.
 *
 * USE FOR UNIT TEST ONLY
 *
 * Example Usage:
 *
 * """
 * $factory = new MappingResourceMetadataFactory($metadataByClass = [
 *     DummyEntity::class => new ResourceMetadata(...),
 * ]);
 *
 * $metadata = $factory->create(DummyEntity::class); // The resource metadata is returned
 * """
 *
 * @author Torrey Tsui <torreytsui@gmail.com>
 */
class MappingResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    /** @var array */
    private $metadataByClass;

    public function __construct(array $metadataByClass)
    {
        $this->metadataByClass = $metadataByClass;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        if ($metadata = $this->metadataByClass[$resourceClass] ?? null) {
            return $metadata;
        }

        throw new \Exception(sprintf('Resource metadata for class "%s" is not found in the mapping. Please configure it.', $resourceClass));
    }
}
