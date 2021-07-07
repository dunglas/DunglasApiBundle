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

namespace ApiPlatform\Core\Bridge\FosUser;

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Bridges between FOSUserBundle and API Platform Core.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class EventListener
{
    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        if (!RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $user = $event->getControllerResult();
        if (!$user instanceof UserInterface || $request->isMethodSafe()) {
            return;
        }

        if ('DELETE' === $request->getMethod()) {
            $this->userManager->deleteUser($user);
            $event->setControllerResult(null);
        } else {
            $this->userManager->updateUser($user);
        }
    }
}
