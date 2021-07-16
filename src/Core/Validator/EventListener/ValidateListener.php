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

namespace ApiPlatform\Core\Validator\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Validates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidateListener
{
    use OperationRequestInitiatorTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'validate';

    private $validator;
    private $resourceMetadataFactory;

    public function __construct(ValidatorInterface $validator, $resourceMetadataFactory = null)
    {
        $this->validator = $validator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }
    }

    /**
     * Validates data returned by the controller if applicable.
     *
     * @throws ValidationException
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || $request->isMethod('DELETE')
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (!$operation || !$operation->canValidate())
        ) {
            return;
        }

        // TODO: 3.0 remove condition
        if (
            !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface && (
            !$attributes['receive']
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
            )
        ) {
            return;
        }

        $validationContext = $operation ? $operation->getValidationContext() : [];

        if (!$validationContext && $this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
            $validationGroups = $resourceMetadata->getOperationAttribute($attributes, 'validation_groups', null, true);
            $validationContext = ['groups' => $validationGroups];
        }

        $this->validator->validate($controllerResult, $validationContext);
    }
}
