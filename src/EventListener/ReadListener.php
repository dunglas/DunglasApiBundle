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
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\NotFoundException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Stage\ReadStage;
use ApiPlatform\Core\Stage\ReadStageInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\RequestParser;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadListener
{
    public const OPERATION_ATTRIBUTE_KEY = 'read';

    private $readStage;
    private $serializerContextBuilder;

    public function __construct(/*CollectionDataProviderInterface */$readStage, /*ItemDataProviderInterface */$serializerContextBuilder/*, SubresourceDataProviderInterface $subresourceDataProvider = null, SerializerContextBuilderInterface $serializerContextBuilder = null, IdentifierConverterInterface $identifierConverter = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null*/)
    {
        $this->readStage = $readStage;
        $this->serializerContextBuilder = $serializerContextBuilder;

        if (\func_num_args() > 2) {
            @trigger_error(sprintf('Not injecting only "%s" and "%s" is deprecated since API Platform 2.5 and will not be possible anymore in API Platform 3', ReadStageInterface::class, SerializerContextBuilderInterface::class), E_USER_DEPRECATED);

            $collectionDataProvider = $readStage;
            $itemDataProvider = $serializerContextBuilder;
            $subresourceDataProvider = func_get_arg(2);
            $serializerContextBuilder = func_get_arg(3);
            $identifierConverter = func_get_arg(4);
            $resourceMetadataFactory = func_get_arg(5);

            $this->readStage = new ReadStage($collectionDataProvider, $itemDataProvider, $subresourceDataProvider, $identifierConverter, $resourceMetadataFactory);
            $this->serializerContextBuilder = $serializerContextBuilder;
        }
    }

    /**
     * Calls the data provider and sets the data attribute.
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $attributes = RequestAttributesExtractor::extractAttributes($request);
        $parameters = $request->attributes->all();
        if (null === $filters = ($parameters['_api_filters'] ?? null)) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        }
        $normalizationContext = [];
        if ($this->serializerContextBuilder) {
            $normalizationContext = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
        }

        try {
            $data = $this->readStage->apply($attributes, $parameters, $filters, $request->getMethod(), $normalizationContext);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e->getPrevious());
        }

        if (null === $data) {
            return;
        }

        if ($normalizationContext) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            $request->attributes->set('_api_normalization_context', $normalizationContext);
        }

        $request->attributes->set('data', $data);
        $request->attributes->set('previous_data', \is_object($data) && (new \ReflectionClass(\get_class($data)))->isCloneable() ? clone $data : $data);
    }
}
