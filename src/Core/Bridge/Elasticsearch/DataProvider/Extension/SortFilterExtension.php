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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\SortFilterInterface;

/**
 * Applies filters on the sort parameter while querying resource collection.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class SortFilterExtension extends AbstractFilterExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterInterface(): string
    {
        return SortFilterInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function alterRequestBody(array $requestBody, array $clauseBody): array
    {
        $requestBody['sort'] = array_merge_recursive($requestBody['sort'] ?? [], $clauseBody);

        return $requestBody;
    }
}
