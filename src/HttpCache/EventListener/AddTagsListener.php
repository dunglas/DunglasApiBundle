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

namespace ApiPlatform\Core\HttpCache\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\JsonLd\Action\ContextAction;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Sets the list of resources' IRIs included in this response in the "Cache-Tags" HTTP header.
 *
 * The "Cache-Tags" is used because it is supported by CloudFlare.
 *
 * @see https://support.cloudflare.com/hc/en-us/articles/206596608-How-to-Purge-Cache-Using-Cache-Tags-Enterprise-only-
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class AddTagsListener
{
    private $iriConverter;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;

    public function __construct(IriConverterInterface $iriConverter, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->iriConverter = $iriConverter;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Adds the "Cache-Tags" header.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->isMethodCacheable() || !$response->isCacheable()) {
            return;
        }

        $resources = $request->attributes->get('_resources');
        $isDocEndpoint = false;

        if (!$resources && $request->attributes->get('_api_respond')) {
            if (($routeParams = $request->attributes->get('_route_params')) && isset($routeParams['shortName'])) {
                if (array_key_exists($routeParams['shortName'], ContextAction::RESERVED_SHORT_NAMES + ['Entrypoint' => true])) {
                    $iri = $this->iriConverter->getContextIriFromShortName($routeParams['shortName']);
                } else {
                    foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
                        if ($routeParams['shortName'] === $this->resourceMetadataFactory->create($resourceClass)->getShortName()) {
                            $iri = $this->iriConverter->getContextIriFromShortName($routeParams['shortName']);
                            break;
                        }
                    }
                }
            } else {
                $iri = $this->iriConverter->getApiDocIri();
            }

            if ($isDocEndpoint = isset($iri)) {
                $resources[$iri] = $iri;
            }
        }

        if (!$isDocEndpoint) {
            if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
                return;
            }

            if (isset($attributes['collection_operation_name'])) {
                // Allows to purge collections
                $iri = $this->iriConverter->getIriFromResourceClass($attributes['resource_class']);
                $resources[$iri] = $iri;
            }

            if (!$resources) {
                return;
            }
        }

        $event->getResponse()->headers->set('Cache-Tags', implode(',', $resources));
    }
}
