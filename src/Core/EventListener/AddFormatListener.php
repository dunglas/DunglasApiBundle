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

use ApiPlatform\Core\Api\FormatMatcher;
use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Chooses the format to use according to the Accept header and supported formats.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddFormatListener
{
    use OperationRequestInitiatorTrait;

    private $negotiator;
    private $resourceMetadataFactory;
    private $formats = [];
    private $formatsProvider;
    private $formatMatcher;

    /**
     * @param ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface|FormatsProviderInterface|array $resourceMetadataFactory
     */
    public function __construct(Negotiator $negotiator, $resourceMetadataFactory, array $formats = [])
    {
        $this->negotiator = $negotiator;
        $this->resourceMetadataFactory = $resourceMetadataFactory instanceof ResourceMetadataFactoryInterface ? $resourceMetadataFactory : null;
        $this->formats = $formats;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceCollectionMetadataFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        } elseif (!$resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            trigger_deprecation('api-plaform/core', '2.5', sprintf('Passing an array or an instance of "%s" as 2nd parameter of the constructor of "%s" is deprecated since API Platform 2.5, pass an instance of "%s" instead', FormatsProviderInterface::class, __CLASS__, ResourceMetadataFactoryInterface::class));
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface ? $resourceMetadataFactory : null;

        if (\is_array($resourceMetadataFactory)) {
            $this->formats = $resourceMetadataFactory;
        } elseif ($resourceMetadataFactory instanceof FormatsProviderInterface) {
            $this->formatsProvider = $resourceMetadataFactory;
        }
    }

    /**
     * Sets the applicable format to the HttpFoundation Request.
     *
     * @throws NotFoundHttpException
     * @throws NotAcceptableHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (!(
            $request->attributes->has('_api_resource_class')
            || $request->attributes->getBoolean('_api_respond', false)
            || $request->attributes->getBoolean('_graphql', false)
        )) {
            return;
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);
        $formats = $this->formats;

        // BC check to be removed in 3.0
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface && $attributes) {
            // TODO: Subresource operation metadata aren't available by default, for now we have to fallback on default formats.
            // TODO: A better approach would be to always populate the subresource operation array.
            $formats = $this
                ->resourceMetadataFactory
                ->create($attributes['resource_class'])
                ->getOperationAttribute($attributes, 'output_formats', $this->formats, true);
        } elseif ($this->formatsProvider instanceof FormatsProviderInterface) {
            $formats = $this->formatsProvider->getFormatsFromAttributes($attributes);
        } elseif ($operation && $operation->getOutputFormats()) {
            $formats = $operation->getOutputFormats();
        }

        $this->addRequestFormats($request, $formats);
        $this->formatMatcher = new FormatMatcher($formats);

        // Empty strings must be converted to null because the Symfony router doesn't support parameter typing before 3.2 (_format)
        if (null === $routeFormat = $request->attributes->get('_format') ?: null) {
            $flattenedMimeTypes = $this->flattenMimeTypes($formats);
            $mimeTypes = array_keys($flattenedMimeTypes);
        } elseif (!isset($formats[$routeFormat])) {
            throw new NotFoundHttpException(sprintf('Format "%s" is not supported', $routeFormat));
        } else {
            $mimeTypes = Request::getMimeTypes($routeFormat);
            $flattenedMimeTypes = $this->flattenMimeTypes([$routeFormat => $mimeTypes]);
        }

        // First, try to guess the format from the Accept header
        /** @var string|null $accept */
        $accept = $request->headers->get('Accept');
        if (null !== $accept) {
            if (null === $mediaType = $this->negotiator->getBest($accept, $mimeTypes)) {
                throw $this->getNotAcceptableHttpException($accept, $flattenedMimeTypes);
            }

            $request->setRequestFormat($this->formatMatcher->getFormat($mediaType->getType()));

            return;
        }

        // Then use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat('') ?: null;
        if (null !== $requestFormat) {
            $mimeType = $request->getMimeType($requestFormat);

            if (isset($flattenedMimeTypes[$mimeType])) {
                return;
            }

            throw $this->getNotAcceptableHttpException($mimeType, $flattenedMimeTypes);
        }

        // Finally, if no Accept header nor Symfony request format is set, return the default format
        foreach ($formats as $format => $mimeType) {
            $request->setRequestFormat($format);

            return;
        }
    }

    /**
     * Adds the supported formats to the request.
     *
     * This is necessary for {@see Request::getMimeType} and {@see Request::getMimeTypes} to work.
     */
    private function addRequestFormats(Request $request, array $formats): void
    {
        foreach ($formats as $format => $mimeTypes) {
            $request->setFormat($format, (array) $mimeTypes);
        }
    }

    /**
     * Retries the flattened list of MIME types.
     */
    private function flattenMimeTypes(array $formats): array
    {
        $flattenedMimeTypes = [];
        foreach ($formats as $format => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $flattenedMimeTypes[$mimeType] = $format;
            }
        }

        return $flattenedMimeTypes;
    }

    /**
     * Retrieves an instance of NotAcceptableHttpException.
     */
    private function getNotAcceptableHttpException(string $accept, array $mimeTypes): NotAcceptableHttpException
    {
        return new NotAcceptableHttpException(sprintf(
            'Requested format "%s" is not supported. Supported MIME types are "%s".',
            $accept,
            implode('", "', array_keys($mimeTypes))
        ));
    }
}
