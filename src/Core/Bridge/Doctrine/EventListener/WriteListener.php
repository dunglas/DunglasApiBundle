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

namespace ApiPlatform\Core\Bridge\Doctrine\EventListener;

use ApiPlatform\Core\EventListener\WriteListener as BaseWriteListener;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Bridges Doctrine and the API system.
 *
 * @deprecated
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class WriteListener
{
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        @trigger_error(sprintf('The %s class is deprecated since version 2.2 and will be removed in 3.0. Use the %s class instead.', __CLASS__, BaseWriteListener::class), \E_USER_DEPRECATED);

        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->isMethodSafe()) {
            return;
        }

        $resourceClass = $request->attributes->get('_api_resource_class');
        if (null === $resourceClass) {
            return;
        }

        $controllerResult = $event->getControllerResult();
        if (null === $objectManager = $this->getManager($resourceClass, $controllerResult)) {
            return;
        }

        switch ($request->getMethod()) {
            case 'POST':
                $objectManager->persist($controllerResult);
                break;
            case 'DELETE':
                $objectManager->remove($controllerResult);
                $event->setControllerResult(null);
                break;
        }

        $objectManager->flush();
    }

    /**
     * Gets the manager if applicable.
     */
    private function getManager(string $resourceClass, $data): ?ObjectManager
    {
        $objectManager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $objectManager || !\is_object($data)) {
            return null;
        }

        return $objectManager;
    }
}
