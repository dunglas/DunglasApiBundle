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

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\OperationDataProviderTrait;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Serializer\SerializerContextFactoryInterface;
use ApiPlatform\Core\Util\CloneTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\RequestParser;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadListener
{
    use CloneTrait;
    use ContextTrait;
    use OperationDataProviderTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'read';

    private $serializerContextFactory;

    /**
     * @param SerializerContextBuilderInterface|SerializerContextFactoryInterface $serializerContextBuilder
     */
    public function __construct(CollectionDataProviderInterface $collectionDataProvider, ItemDataProviderInterface $itemDataProvider, SubresourceDataProviderInterface $subresourceDataProvider = null, /* SerializerContextFactoryInterface $serializerContextFactory */$serializerContextBuilder = null, IdentifierConverterInterface $identifierConverter = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->itemDataProvider = $itemDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->serializerContextFactory = $serializerContextBuilder;
        $this->identifierConverter = $identifierConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Calls the data provider and sets the data attribute.
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
            || $request->isMethod('POST') && isset($attributes['collection_operation_name'])
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        ) {
            return;
        }

        if (null === $filters = $request->attributes->get('_api_filters')) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        }

        $context = null === $filters ? [] : ['filters' => $filters];
        if ($this->serializerContextFactory) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            if ($this->serializerContextFactory instanceof SerializerContextBuilderInterface) {
                $normalizationContext = $this->serializerContextFactory->createFromRequest($request, false, $attributes);
            } else {
                $operationName = $this->getOperationNameFromContext($attributes);
                $normalizationContext = $this->addRequestContext($request, $attributes);
                $normalizationContext = $this->serializerContextFactory->create($attributes['resource_class'], $operationName, false, $normalizationContext);
            }
            $context += $normalizationContext;
            $request->attributes->set('_api_normalization_context', $normalizationContext);
        }

        if (isset($attributes['collection_operation_name'])) {
            $request->attributes->set('data', $this->getCollectionData($attributes, $context));

            return;
        }

        $data = [];

        if ($this->identifierConverter) {
            $context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] = true;
        }

        try {
            $identifiers = $this->extractIdentifiers($request->attributes->all(), $attributes);

            if (isset($attributes['item_operation_name'])) {
                $data = $this->getItemData($identifiers, $attributes, $context);
            } elseif (isset($attributes['subresource_operation_name'])) {
                // Legacy
                if (null === $this->subresourceDataProvider) {
                    throw new RuntimeException('No subresource data provider.');
                }

                $data = $this->getSubresourceData($identifiers, $attributes, $context);
            }
        } catch (InvalidIdentifierException $e) {
            throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
        }

        if (null === $data) {
            throw new NotFoundHttpException('Not Found');
        }

        $request->attributes->set('data', $data);
        $request->attributes->set('previous_data', $this->clone($data));
    }
}
