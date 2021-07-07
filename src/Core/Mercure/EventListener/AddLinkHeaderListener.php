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

namespace ApiPlatform\Core\Mercure\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\CorsTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Mercure\Discovery;

/**
 * Adds the HTTP Link header pointing to the Mercure hub for resources having their updates dispatched.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    use CorsTrait;

    private $resourceMetadataFactory;
    private $discovery;

    /**
     * @param Discovery|string $discovery
     */
    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory = null, $discovery)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        if ($resourceMetadataFactory) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0.', ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
        }
        $this->discovery = $discovery;
    }

    /**
     * Sends the Mercure header on each response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($this->isPreflightRequest($request)) {
            return;
        }

        if (
            null === ($resourceClass = $request->attributes->get('_api_resource_class')) ||
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        $mercure = $attributes['mercure'] ?? false;
        if ($this->resourceMetadataFactory) {
            $mercure = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('mercure', false);
        }

        if (!$mercure) {
            return;
        }

        if (!$this->discovery instanceof Discovery) {
            $link = new Link('mercure', $this->discovery);
            if (null === $linkProvider = $request->attributes->get('_links')) {
                $request->attributes->set('_links', new GenericLinkProvider([$link]));

                return;
            }

            $request->attributes->set('_links', $linkProvider->withLink($link));

            return;
        }

        $hub = \is_array($mercure) ? ($mercure['hub'] ?? null) : null;

        $this->discovery->addLink($request, $hub);
    }
}
