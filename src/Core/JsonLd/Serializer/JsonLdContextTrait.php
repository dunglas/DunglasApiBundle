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

namespace ApiPlatform\Core\JsonLd\Serializer;

use ApiPlatform\Core\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;

/**
 * Creates and manipulates the Serializer context.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait JsonLdContextTrait
{
    /**
     * Updates the given JSON-LD document to add its @context key.
     */
    private function addJsonLdContext(ContextBuilderInterface $contextBuilder, string $resourceClass, array &$context, array $data = []): array
    {
        if (isset($context['jsonld_has_context'])) {
            return $data;
        }

        $context['jsonld_has_context'] = true;

        if (isset($context['jsonld_embed_context'])) {
            $data['@context'] = $contextBuilder->getResourceContext($resourceClass);

            return $data;
        }

        $data['@context'] = $contextBuilder->getResourceContextUri($resourceClass);

        return $data;
    }

    private function createJsonLdContext(AnonymousContextBuilderInterface $contextBuilder, $object, array &$context, array $data = []): array
    {
        // We're in a collection, don't add the @context part
        if (isset($context['jsonld_has_context'])) {
            return $contextBuilder->getAnonymousResourceContext($object, ($context['output'] ?? []) + ['api_resource' => $context['api_resource'] ?? null, 'has_context' => true]);
        }

        $context['jsonld_has_context'] = true;

        return $contextBuilder->getAnonymousResourceContext($object, ($context['output'] ?? []) + ['api_resource' => $context['api_resource'] ?? null]);
    }
}
