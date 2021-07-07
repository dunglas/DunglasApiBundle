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

namespace ApiPlatform\Core\Action;

/**
 * Placeholder returning the data passed in parameter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PlaceholderAction
{
    /**
     * @param object $data
     *
     * @return object
     */
    public function __invoke($data)
    {
        return $data;
    }
}
