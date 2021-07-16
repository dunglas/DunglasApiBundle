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

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use IteratorAggregate;

#[ApiResource('/attribute_resources.{_format}', normalizationContext: ['skip_null_values' => true])]
#[GetCollection]
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
