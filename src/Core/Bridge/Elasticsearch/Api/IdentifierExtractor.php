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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Api;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\NonUniqueIdentifierException;

/**
 * {@inheritdoc}
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class IdentifierExtractor implements IdentifierExtractorInterface
{
    private $identifiersExtractor;

    public function __construct(IdentifiersExtractorInterface $identifiersExtractor)
    {
        $this->identifiersExtractor = $identifiersExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFromResourceClass(string $resourceClass): string
    {
        $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass);

        if (0 >= $totalIdentifiers = \count($identifiers)) {
            throw new NonUniqueIdentifierException(sprintf('Resource "%s" has no identifiers.', $resourceClass));
        }

        if (1 < $totalIdentifiers) {
            throw new NonUniqueIdentifierException('Composite identifiers not supported.');
        }

        return reset($identifiers);
    }
}
