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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondListener
{
    use OperationRequestInitiatorTrait;

    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    private $resourceMetadataFactory;

    public function __construct($resourceMetadataFactory = null)
    {
        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', ResourceMetadataFactoryInterface::class, ResourceMetadataCollectionFactoryInterface::class), \E_USER_DEPRECATED);
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Creates a Response to send to the client according to the requested format.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        $attributes = RequestAttributesExtractor::extractAttributes($request);
        if ($controllerResult instanceof Response || $request->attributes->getBoolean('_api_respond', false)) {
            return;
        }

        if ($controllerResult instanceof Response) {
            $event->setResponse($controllerResult);

            return;
        }

        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ];

        $status = null;
        if ($this->resourceMetadataFactory && $attributes) {
            // TODO: remove this in 3.x
            if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

                if ($sunset = $resourceMetadata->getOperationAttribute($attributes, 'sunset', null, true)) {
                    $headers['Sunset'] = (new \DateTimeImmutable($sunset))->format(\DateTime::RFC1123);
                }

                $headers = $this->addAcceptPatchHeader($headers, $attributes, $resourceMetadata);
                $status = $resourceMetadata->getOperationAttribute($attributes, 'status');
            } else {
                if ($sunset = $operation->getSunset()) {
                    $headers['Sunset'] = (new \DateTimeImmutable($sunset))->format(\DateTime::RFC1123);
                }

                $status = $operation->getStatus();

                if ($status) {
                    $status = (int) $status;
                }

                if ($acceptPatch = $operation->getAcceptPatch()) {
                    $headers['Accept-Patch'] = $acceptPatch;
                }
            }
        }

        $status = $status ?? self::METHOD_TO_CODE[$request->getMethod()] ?? Response::HTTP_OK;

        if ($request->attributes->has('_api_write_item_iri')) {
            $headers['Content-Location'] = $request->attributes->get('_api_write_item_iri');

            if ((Response::HTTP_CREATED === $status || (300 <= $status && $status < 400)) && $request->isMethod('POST')) {
                $headers['Location'] = $request->attributes->get('_api_write_item_iri');
            }
        }

        $event->setResponse(new Response(
            $controllerResult,
            $status,
            $headers
        ));
    }

    private function addAcceptPatchHeader(array $headers, array $attributes, ResourceMetadata $resourceMetadata): array
    {
        if (!isset($attributes['item_operation_name'])) {
            return $headers;
        }

        $patchMimeTypes = [];
        foreach ($resourceMetadata->getItemOperations() as $operation) {
            if ('PATCH' !== ($operation['method'] ?? '') || !isset($operation['input_formats'])) {
                continue;
            }

            foreach ($operation['input_formats'] as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $patchMimeTypes[] = $mimeType;
                }
            }
            $headers['Accept-Patch'] = implode(', ', $patchMimeTypes);

            return $headers;
        }

        return $headers;
    }
}
