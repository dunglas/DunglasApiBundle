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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource;
use IteratorAggregate;

#[Resource('/attribute_resources.{_format}')]
#[Get]
#[Post]
final class AttributeResources implements IteratorAggregate
{
    /**
     * @var AttributeResource[]
     */
    private $collection;

    public function __construct(AttributeResource ...$collection)
    {
        $this->collection = $collection;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }
}
