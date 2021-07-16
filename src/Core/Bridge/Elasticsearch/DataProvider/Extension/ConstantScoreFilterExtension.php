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

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\ConstantScoreFilterInterface;

/**
 * Applies filter clauses while executing a constant score query.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-constant-score-query.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ConstantScoreFilterExtension extends AbstractFilterExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterInterface(): string
    {
        return ConstantScoreFilterInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function alterRequestBody(array $requestBody, array $clauseBody): array
    {
        $requestBody['query'] = $requestBody['query'] ?? [];
        $requestBody['query'] += [
            'constant_score' => [
                'filter' => $clauseBody,
            ],
        ];

        return $requestBody;
    }
}
