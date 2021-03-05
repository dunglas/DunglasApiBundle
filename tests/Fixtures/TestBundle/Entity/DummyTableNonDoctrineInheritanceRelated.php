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

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"default"}},
 *         "denormalization_context"={"groups"={"default"}}
 *     }
 * )
 */
class DummyTableNonDoctrineInheritanceRelated
{
    /**
     * @var int The id
     *
     * @Groups({"default"})
     */
    public $id;

    /**
     * @var DummyTableNonDoctrineInheritance[]|null
     *
     * @Groups({"default"})
     */
    public $children;
}
