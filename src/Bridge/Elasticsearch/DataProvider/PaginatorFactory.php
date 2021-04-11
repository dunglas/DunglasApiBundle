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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\DataProvider\PaginatorFactoryInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PaginatorFactory implements PaginatorFactoryInterface
{
    private $denormalizer;

    public function __construct(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function createPaginator($subject, int $limit, int $offset, array $context = []): PaginatorInterface
    {
        $resourceClass = $context['resource_class'] ?? null;

        if (null === $resourceClass) {
            throw new RuntimeException('The given context array is missing the "resource_class" key.');
        }

        return new Paginator(
            $this->denormalizer,
            $subject,
            $resourceClass,
            $limit,
            $offset,
            $context
        );
    }
}
