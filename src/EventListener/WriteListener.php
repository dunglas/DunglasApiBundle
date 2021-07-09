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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
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
    use ResourceClassInfoTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'write';

    private $dataPersister;
    private $iriConverter;

    public function __construct(DataPersisterInterface $dataPersister, IriConverterInterface $iriConverter = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->dataPersister = $dataPersister;
        $this->iriConverter = $iriConverter;
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

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['persist']
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        ) {
            return;
        }

        if (!$this->dataPersister->supports($controllerResult, $attributes)) {
            return;
        }

        switch ($request->getMethod()) {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                $persistResult = $this->dataPersister->persist($controllerResult, $attributes);

                if (!\is_object($persistResult)) {
                    @trigger_error(sprintf('Not returning an object from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3.', DataPersisterInterface::class), \E_USER_DEPRECATED);
                } else {
                    $controllerResult = $persistResult;
                    $event->setControllerResult($controllerResult);
                }

                if ($controllerResult instanceof Response) {
                    break;
                }

                $hasOutput = true;
                $resourceMetadata = null;
                if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
                    $outputMetadata = $resourceMetadata->getOperationAttribute($attributes, 'output', [
                        'class' => $attributes['resource_class'],
                    ], true);

                    $hasOutput = \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
                }

                if (null !== $resourceMetadata) {
                    $hasOutput = $resourceMetadata->getOperationAttribute($attributes, 'set_content_location', $hasOutput, true);
                }

                if ($hasOutput && $this->iriConverter instanceof IriConverterInterface && $this->isResourceClass($this->getObjectClass($controllerResult))) {
                    $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromItem($controllerResult));
                }

                break;
            case 'DELETE':
                $this->dataPersister->remove($controllerResult, $attributes);
                $event->setControllerResult(null);
                break;
        }
    }
}
