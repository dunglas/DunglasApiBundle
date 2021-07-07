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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Validator\EventListener\ValidateListener as MainValidateListener;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates data.
 *
 * @deprecated
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidateListener
{
    private $validator;
    private $resourceMetadataFactory;
    private $container;

    public function __construct(ValidatorInterface $validator, ResourceMetadataFactoryInterface $resourceMetadataFactory, ContainerInterface $container = null)
    {
        @trigger_error(sprintf('Using "%s" is deprecated since API Platform 2.2 and will not be possible anymore in API Platform 3. Use "%s" instead.', __CLASS__, MainValidateListener::class), \E_USER_DEPRECATED);

        $this->validator = $validator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->container = $container;
    }

    /**
     * Validates data returned by the controller if applicable.
     *
     * @throws ValidationException
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        if (
            $request->isMethodSafe()
            || $request->isMethod('DELETE')
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
        ) {
            return;
        }

        $data = $event->getControllerResult();
        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

        $validationGroups = $resourceMetadata->getOperationAttribute($attributes, 'validation_groups');

        if (!$validationGroups) {
            // Fallback to the resource
            $validationGroups = $resourceMetadata->getAttributes()['validation_groups'] ?? null;
        }

        if (
            $this->container &&
            \is_string($validationGroups) &&
            $this->container->has($validationGroups) &&
            ($service = $this->container->get($validationGroups)) &&
            \is_callable($service)
        ) {
            $validationGroups = $service($data);
        } elseif (\is_callable($validationGroups)) {
            $validationGroups = $validationGroups($data);
        }

        $violations = $this->validator->validate($data, null, (array) $validationGroups);
        if (0 !== \count($violations)) {
            throw new ValidationException($violations);
        }
    }
}
