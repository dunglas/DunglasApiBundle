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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\ExceptionInterface;

interface PaginatorFactoryInterface
{
    /**
     * Creates a new {@see PaginatorInterface} concrete instance.
     *
     * @param mixed                $subject The subject to paginate (array, ORM query, etc.)
     * @param int                  $limit   The maximum number of records to fetch
     * @param int                  $offset  The starting index from which to fetch the records
     * @param array<string, mixed> $context The associative array context for the paginator
     *
     * @throws ExceptionInterface Whenever something wrong occurs
     */
    public function createPaginator($subject, int $limit, int $offset, array $context = []): PaginatorInterface;
}
