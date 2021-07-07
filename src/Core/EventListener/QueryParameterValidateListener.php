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

use ApiPlatform\Core\Filter\QueryParameterValidator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\RequestParser;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
final class QueryParameterValidateListener
{
    use OperationRequestInitiatorTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'query_parameter_validate';

    private $resourceMetadataFactory;

    private $queryParameterValidator;

    private $enabled;

    public function __construct($resourceMetadataFactory, QueryParameterValidator $queryParameterValidator, bool $enabled = true)
    {
        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceCollectionMetadataFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        } else {
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->queryParameterValidator = $queryParameterValidator;
        $this->enabled = $enabled;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);
        if (
            !$request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || 'GET' !== $request->getMethod()
        ) {
            return;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (!$operation || !$operation->canQueryParameterValidate() || !$operation->isCollection())
        ) {
            return;
        }

        // TODO: remove in 3.0
        if (!$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (
            !isset($attributes['collection_operation_name'])
            || !($operationName = $attributes['collection_operation_name'])
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY, !$this->enabled)
            )
        ) {
            return;
        }

        $queryString = RequestParser::getQueryString($request);
        $queryParameters = $queryString ? RequestParser::parseRequestParams($queryString) : [];

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        $this->queryParameterValidator->validateFilters($attributes['resource_class'], $resourceFilters, $queryParameters);
    }
}
