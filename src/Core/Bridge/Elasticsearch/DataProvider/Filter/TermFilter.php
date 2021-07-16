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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter;

/**
 * Filter the collection by given properties using a term level query.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TermFilter extends AbstractSearchFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getQuery(string $property, array $values, ?string $nestedPath): array
    {
        if (1 === \count($values)) {
            $termQuery = ['term' => [$property => reset($values)]];
        } else {
            $termQuery = ['terms' => [$property => $values]];
        }

        if (null !== $nestedPath) {
            $termQuery = ['nested' => ['path' => $nestedPath, 'query' => $termQuery]];
        }

        return $termQuery;
    }
}
