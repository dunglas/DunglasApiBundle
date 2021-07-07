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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

use Doctrine\ORM\Query;

interface QueryAwareInterface
{
    /**
     * Gets the Query object that will actually be executed.
     *
     * This should allow configuring options which could only be set on the Query
     * object itself.
     */
    public function getQuery(): Query;
}
