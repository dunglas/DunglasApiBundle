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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Associates the resource retrieved by the resource data provider with the subresource demoralized from the request body.
 *
 * @author Torrey Tsui <torreytsui@gmail.com>
 */
final class AssociateListener
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Associate the resource with the subresource.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $method = $request->getMethod();

        if (
            'DELETE' === $method
            || 'GET' === $method
            || $request->isMethodSafe(false)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
            || null === ($resourceData = $request->attributes->get('resource_data'))
        ) {
            return;
        }

        // Cannot introduce a new AssociateListener as it is an edge case and EventPriority is full
        // Can hook up with POST_DESERIALIZE and build association it with a service - which offers extension point
        // Can stick with DeserializeListener for the time being and use the service - this however gives no meaning reason to couple with the DeserializeListener

        // TODO: Extract to ease customisation? DataAssociatorInterface->support($aClass, $bClass) and associate($a, $b). What about constructor?
        // Maybe? DataDependencyProviderInterface->support($class, $operation) and provide($request)
        // TODO: associates
        // POST - an array, which use add method
        // PUT - if it has id, it already exists (The property is a collection)
        // PUT - without an id, don't know if it's already exist (The property is an item)
        $value = $request->attributes->get('data');
        if ($attributes['subresource_context']['collection']) {
            $propertyValue = $this->propertyAccessor->getValue($resourceData, $attributes['subresource_context']['property']);
            if ($propertyValue instanceof \Traversable) {
                $propertyValue = iterator_to_array($propertyValue);
            }
            $value = array_merge($propertyValue, [$value]);
        }
        $this->propertyAccessor->setValue($resourceData, $attributes['subresource_context']['property'], $value);
    }
}
