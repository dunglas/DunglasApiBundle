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

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Serializer\ResourceList;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializeListener
{
    use OperationRequestInitiatorTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'serialize';

    private $serializer;
    private $serializerContextBuilder;

    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder, $resourceMetadataFactory = null)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('The use of %s is deprecated, use %s instead.', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class));
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Serializes the data to the requested format.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if ($controllerResult instanceof Response) {
            return;
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        // TODO: 3.0 remove condition
        if (
            !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (
                !($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond', false))
                || ($attributes && $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY))
            )
        ) {
            return;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            ($operation && !$operation->canSerialize())
        ) {
            return;
        }

        if (!$attributes) {
            $this->serializeRawData($event, $request, $controllerResult);

            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
        if (isset($context['output']) && \array_key_exists('class', $context['output']) && null === $context['output']['class']) {
            $event->setControllerResult(null);

            return;
        }

        if ($included = $request->attributes->get('_api_included')) {
            $context['api_included'] = $included;
        }
        $resources = new ResourceList();
        $context['resources'] = &$resources;
        $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources';

        $resourcesToPush = new ResourceList();
        $context['resources_to_push'] = &$resourcesToPush;
        $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources_to_push';

        $request->attributes->set('_api_normalization_context', $context);
        $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $context));

        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);
        if (!\count($resourcesToPush)) {
            return;
        }

        $linkProvider = $request->attributes->get('_links', new GenericLinkProvider());
        foreach ($resourcesToPush as $resourceToPush) {
            $linkProvider = $linkProvider->withLink((new Link('preload', $resourceToPush))->withAttribute('as', 'fetch'));
        }
        $request->attributes->set('_links', $linkProvider);
    }

    /**
     * Tries to serialize data that are not API resources (e.g. the entrypoint or data returned by a custom controller).
     *
     * @throws RuntimeException
     */
    private function serializeRawData(ViewEvent $event, Request $request, $controllerResult): void
    {
        if (\is_object($controllerResult)) {
            $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $request->attributes->get('_api_normalization_context', [])));

            return;
        }

        if (!$this->serializer instanceof EncoderInterface) {
            throw new RuntimeException(sprintf('The serializer must implement the "%s" interface.', EncoderInterface::class));
        }

        $event->setControllerResult($this->serializer->encode($controllerResult, $request->getRequestFormat()));
    }
}
