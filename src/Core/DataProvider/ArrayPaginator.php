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

/**
 * Paginator for arrays.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ArrayPaginator implements \IteratorAggregate, PaginatorInterface
{
    private $iterator;
    private $firstResult;
    private $maxResults;
    private $totalItems;

    public function __construct(array $results, int $firstResult, int $maxResults)
    {
        if ($maxResults > 0) {
            $this->iterator = new \LimitIterator(new \ArrayIterator($results), $firstResult, $maxResults);
        } else {
            $this->iterator = new \EmptyIterator();
        }
        $this->firstResult = $firstResult;
        $this->maxResults = $maxResults;
        $this->totalItems = \count($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return ceil($this->totalItems / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return iterator_count($this->iterator);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->iterator;
    }
}
