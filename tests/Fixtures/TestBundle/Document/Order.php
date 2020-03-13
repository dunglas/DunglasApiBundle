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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"normalization_context"={"groups"={"order_read"}}},
 *     forceEager=false
 * )
 * @ODM\Document
 */
class Order
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     * @Groups({"order_read"})
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Customer::class)
     * @Groups({"order_read"})
     */
    public $customer;

    /**
     * @ODM\ReferenceOne(targetDocument=Customer::class)
     * @Assert\NotNull
     * @Groups({"order_read"})
     */
    public $recipient;

    /**
     * @ODM\Field
     * @Groups({"order_admin"})
     */
    public $customerIp;

    public function getId()
    {
        return $this->id;
    }
}
