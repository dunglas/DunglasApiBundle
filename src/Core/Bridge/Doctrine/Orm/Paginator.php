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

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Doctrine\ORM\Query;

/**
 * Decorates the Doctrine ORM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Paginator extends AbstractPaginator implements PaginatorInterface, QueryAwareInterface
{
    /**
     * @var int
     */
    private $totalItems;

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return (float) ($this->totalItems ?? $this->totalItems = \count($this->paginator));
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): Query
    {
        return $this->paginator->getQuery();
    }
}
