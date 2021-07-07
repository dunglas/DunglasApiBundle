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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Validates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidateListener
{
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'validate';

    private $validator;
    private $resourceMetadataFactory;

    public function __construct(ValidatorInterface $validator, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->validator = $validator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($this->resourceMetadataFactory) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0.', ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
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

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || $request->isMethod('DELETE')
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        ) {
            return;
        }

        $validationContext = $attributes['operation']['validation_context'] ?? [];

        if (!isset($attributes['operation']) && $this->resourceMetadataFactory) {
            @trigger_error('When using a "route_name", be sure to define the "_api_operation" route defaults as we will not rely on metadata in API Platform 3.0.', \E_USER_DEPRECATED);
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
            $validationGroups = $resourceMetadata->getOperationAttribute($attributes, 'validation_groups', null, true);
            $validationContext = ['groups' => $validationGroups];
        }

        $this->validator->validate($controllerResult, $validationContext);
    }
}
