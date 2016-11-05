<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\PathResolver;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Inflector\Inflector;

/**
 * Generates a path with words separated by underscores.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 * @author Jérémy Leherpeur <jeremy@leherpeur.net>
 */
final class UnderscoreOperationPathResolver implements OperationPathResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(ResourceMetadata $resourceMetadata, array $operation, bool $collection) : string
    {
        $path = '/'.Inflector::pluralize(Inflector::tableize($resourceMetadata->getShortName()));

        if (!$collection) {
            $path .= '/{id}';
        }

        $path .= '.{_format}';

        return $path;
    }
}
