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

use Psr\Container\ContainerInterface;

/**
 * A list of filters.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @deprecated since version 2.1, to be removed in 3.0. Use a service locator {@see \Psr\Container\ContainerInterface}.
 */
final class FilterCollection extends \ArrayObject
{
    public function __construct($input = [], $flags = 0, $iterator_class = 'ArrayIterator')
    {
        @trigger_error(sprintf('The %s class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of %s instead.', self::class, ContainerInterface::class), \E_USER_DEPRECATED);

        parent::__construct($input, $flags, $iterator_class);
    }
}
