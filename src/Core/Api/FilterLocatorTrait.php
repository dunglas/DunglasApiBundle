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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Manipulates filters with a backward compatibility between the new filter locator and the deprecated filter collection.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @internal
 */
trait FilterLocatorTrait
{
    private $filterLocator;

    /**
     * Sets a filter locator with a backward compatibility.
     *
     * @param ContainerInterface|FilterCollection|null $filterLocator
     */
    private function setFilterLocator($filterLocator, bool $allowNull = false): void
    {
        if ($filterLocator instanceof ContainerInterface || $filterLocator instanceof FilterCollection || (null === $filterLocator && $allowNull)) {
            if ($filterLocator instanceof FilterCollection) {
                @trigger_error(sprintf('The %s class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of %s instead.', FilterCollection::class, ContainerInterface::class), \E_USER_DEPRECATED);
            }

            $this->filterLocator = $filterLocator;
        } else {
            throw new InvalidArgumentException(sprintf('The "$filterLocator" argument is expected to be an implementation of the "%s" interface%s.', ContainerInterface::class, $allowNull ? ' or null' : ''));
        }
    }

    /**
     * Gets a filter with a backward compatibility.
     */
    private function getFilter(string $filterId): ?FilterInterface
    {
        if ($this->filterLocator instanceof ContainerInterface && $this->filterLocator->has($filterId)) {
            return $this->filterLocator->get($filterId);
        }

        if ($this->filterLocator instanceof FilterCollection && $this->filterLocator->offsetExists($filterId)) {
            return $this->filterLocator->offsetGet($filterId);
        }

        return null;
    }
}
