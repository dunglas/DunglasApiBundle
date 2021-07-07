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

namespace ApiPlatform\Core\Metadata\Property;

/**
 * A collection of property names for a given resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PropertyNameCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var string[]
     */
    private $properties;

    /**
     * @param string[] $properties
     */
    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->properties);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return \count($this->properties);
    }
}
