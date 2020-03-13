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

namespace ApiPlatform\Core\Serializer;

use Symfony\Component\HttpFoundation\Request;

/**
 * Creates and manipulates the Serializer context.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait ContextTrait
{
    /**
     * Initializes the context.
     */
    private function initContext(string $resourceClass, array $context): array
    {
        return array_merge($context, [
            'api_sub_level' => true,
            'resource_class' => $resourceClass,
        ]);
    }

    private function addRequestContext(Request $request, array $context): array
    {
        return array_merge($context, [
            'request_query' => $request->query->all(),
            'request_attributes' => $request->attributes->all(),
            'request_method' => $request->getMethod(),
            'request_content_type' => $request->getContentType(),
            'request_request_uri' => $request->getRequestUri(),
            'request_uri' => $request->getUri(),
        ]);
    }

    private function getOperationNameFromContext(array $context): string
    {
        $operationName = $context['subresource_operation_name'] ?? null;
        if (isset($context['collection_operation_name'])) {
            $operationName = $context['collection_operation_name'];
        } elseif (isset($context['item_operation_name'])) {
            $operationName = $context['item_operation_name'];
        } elseif (isset($context['resource_operation_name'])) {
            $operationName = $context['resource_operation_name'];
        }

        if (!\is_string($operationName)) {
            throw new \RuntimeException('Operation name cannot be extracted');
        }

        return $operationName;
    }
}
