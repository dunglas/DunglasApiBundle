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

use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

abstract class AbstractPaginator implements \IteratorAggregate, PartialPaginatorInterface
{
    protected $paginator;
    protected $iterator;
    protected $firstResult;
    protected $maxResults;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(DoctrinePaginator $paginator)
    {
        $query = $paginator->getQuery();

        if (null === ($firstResult = $query->getFirstResult()) || null === $maxResults = $query->getMaxResults()) {
            throw new InvalidArgumentException(sprintf('"%1$s::setFirstResult()" or/and "%1$s::setMaxResults()" was/were not applied to the query.', Query::class));
        }

        $this->paginator = $paginator;
        $this->firstResult = $firstResult;
        $this->maxResults = $maxResults;
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
    public function getItemsPerPage(): float
    {
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->iterator ?? $this->iterator = $this->paginator->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }
}
