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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Bridges persistence and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class WriteListener
{
    use OperationRequestInitiatorTrait;
    use ResourceClassInfoTrait;

    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'write';

    private $dataPersister;
    private $iriConverter;

    public function __construct($dataPersister, $iriConverter = null, $resourceMetadataFactory = null, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        if ($dataPersister instanceof DataPersisterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', DataPersisterInterface::class, ProcessorInterface::class));
        }
        $this->dataPersister = $dataPersister;

        if ($iriConverter instanceof LegacyIriConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', IriConverterInterface::class, LegacyIriConverterInterface::class));
        }

        $this->iriConverter = $iriConverter;
        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        } else {
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        $operation = $this->initializeOperation($request);

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (!$operation || !$operation->canWrite())
        ) {
            return;
            // TODO: 3.0 remove condition
        }

        if (
            !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (!$attributes['persist']
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        )) {
            return;
        }

        if ($this->dataPersister instanceof DataPersisterInterface && !$this->dataPersister->supports($controllerResult, $attributes)) {
            return;
        }

        $context = ['operation' => $operation, 'legacy_attributes' => $attributes + ['identifiers' => $operation->getIdentifiers(), 'has_composite_identifier' => $operation->getCompositeIdentifier()]];
        if ($this->dataPersister instanceof ProcessorInterface && !$this->dataPersister->supports($controllerResult, $attributes['identifiers'], $operation->getName(), $context)) {
            return;
        }

        switch ($request->getMethod()) {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                $persistResult = $this->dataPersister instanceof DataPersisterInterface ? $this->dataPersister->persist($controllerResult, $attributes) : $this->dataPersister->process($controllerResult, $operation->getIdentifiers(), $operation->getName(), $context);

                if ($this->dataPersister instanceof DataPersisterInterface && !\is_object($persistResult)) {
                    @trigger_error(sprintf('Not returning an object from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3.', DataPersisterInterface::class), \E_USER_DEPRECATED);
                } elseif ($persistResult) {
                    $controllerResult = $persistResult;
                    $event->setControllerResult($controllerResult);
                }

                if ($controllerResult instanceof Response) {
                    break;
                }

                $outputMetadata = $operation && $operation->getOutput() ? $operation->getOutput() : ['class' => $attributes['resource_class']];
                if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
                    $outputMetadata = $resourceMetadata->getOperationAttribute($attributes, 'output', [
                        'class' => $attributes['resource_class'],
                    ], true);
                }

                $hasOutput = \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
                if (!$hasOutput) {
                    break;
                }

                if ($this->isResourceClass($resourceClass = $this->getObjectClass($controllerResult))) {
                    $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromItem($controllerResult));
                }

                break;
            case 'DELETE':
                $this->dataPersister instanceof DataPersisterInterface ? $this->dataPersister->remove($controllerResult, $attributes) : $this->dataPersister->process($controllerResult, $operation->getIdentifiers(), $operation->getName(), $context);
                $event->setControllerResult(null);
                break;
        }
    }
}
