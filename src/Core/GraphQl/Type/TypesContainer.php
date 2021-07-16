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

namespace ApiPlatform\Core\GraphQl\Type;

use GraphQL\Type\Definition\Type as GraphQLType;

/**
 * Container having the built GraphQL types.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypesContainer implements TypesContainerInterface
{
    private $graphqlTypes = [];

    /**
     * {@inheritdoc}
     */
    public function set(string $id, GraphQLType $type): void
    {
        $this->graphqlTypes[$id] = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id): GraphQLType
    {
        if ($this->has($id)) {
            return $this->graphqlTypes[$id];
        }

        throw new TypeNotFoundException(sprintf('Type with id "%s" is not present in the types container', $id), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->graphqlTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id): bool
    {
        return \array_key_exists($id, $this->graphqlTypes);
    }
}
