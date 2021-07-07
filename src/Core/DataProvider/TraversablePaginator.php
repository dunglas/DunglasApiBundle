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

final class TraversablePaginator implements \IteratorAggregate, PaginatorInterface
{
    private $traversable;
    private $currentPage;
    private $itemsPerPage;
    private $totalItems;

    public function __construct(\Traversable $iterator, float $currentPage, float $itemsPerPage, float $totalItems)
    {
        $this->traversable = $iterator;
        $this->currentPage = $currentPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalItems = $totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0. >= $this->itemsPerPage) {
            return 1.;
        }

        return max(ceil($this->totalItems / $this->itemsPerPage) ?: 1., 1.);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return $this->totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if ($this->getCurrentPage() < $this->getLastPage()) {
            return (int) ceil($this->itemsPerPage);
        }

        if (0. >= $this->itemsPerPage) {
            return (int) ceil($this->totalItems);
        }

        return $this->totalItems % $this->itemsPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->traversable;
    }
}
