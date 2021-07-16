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

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Cache\CachedTrait;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches property metadata.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use CachedTrait;

    public const CACHE_KEY_PREFIX = 'property_metadata_';

    private $decorated;

    public function __construct(CacheItemPoolInterface $cacheItemPool, PropertyMetadataFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $property, $options]));

        return $this->getCached($cacheKey, function () use ($resourceClass, $property, $options) {
            return $this->decorated->create($resourceClass, $property, $options);
        });
    }
}
