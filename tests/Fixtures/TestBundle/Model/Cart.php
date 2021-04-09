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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class Cart
{
    /**
     * @ApiProperty(identifier=true)
     */
    public $id = 'an-identifier';

    /**
     * @var ShippingAddress|null
     */
    public $shippingAddress = null;
}
