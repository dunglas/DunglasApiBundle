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

namespace ApiPlatform\Core\HttpCache;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\JsonLd\Action\ContextAction;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Purges Varnish on cache clearing only for doc tags.
 *
 * @author Florent Mata <florentmata@gmail.com>
 *
 * @experimental
 */
final class VarnishClearer implements CacheClearerInterface
{
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $iriConverter;
    private $purger;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, IriConverterInterface $iriConverter, PurgerInterface $purger)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->purger = $purger;
    }

    public function clear($cacheDir)
    {
        $iri = $this->iriConverter->getApiDocIri();
        $iris = [$iri => $iri];

        foreach (array_keys(['Entrypoint' => true] + ContextAction::RESERVED_SHORT_NAMES) as $shortName) {
            $iri = $this->iriConverter->getContextIriFromShortName($shortName);
            $iris[$iri] = $iri;
        }

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $iri = $this->iriConverter->getContextIriFromShortName($this->resourceMetadataFactory->create($resourceClass)->getShortName());
            $iris[$iri] = $iri;
        }

        $this->purger->purge($iris);
    }
}
