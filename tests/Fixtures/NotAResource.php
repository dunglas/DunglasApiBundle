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

namespace ApiPlatform\Core\Tests\Fixtures;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * This class is not mapped as an API resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class NotAResource
{
    /**
     * @ApiProperty(identifier=true)
     */
    private $id;

    /**
     * @Groups("contain_non_resource")
     */
    private $foo;

    /**
     * @Groups("contain_non_resource")
     */
    private $bar;

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getId(): string
    {
        return md5($this->foo.$this->bar);
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }
}
