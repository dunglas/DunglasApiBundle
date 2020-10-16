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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\CustomInputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\CustomOutputDto;
use Doctrine\ORM\Mapping as ORM;

/**
 * DummyDtoCustom.
 *
 * @ORM\Entity
 *
 * @ApiResource(
 *     collectionOperations={
 *         "post"={
 *             "input"=CustomInputDto::class
 *         },
 *         "get",
 *         "custom_output"={
 *             "output"=CustomOutputDto::class,
 *             "path"="dummy_dto_custom_output",
 *             "method"="GET"
 *         },
 *         "post_without_output"={
 *             "output"=false,
 *             "method"="POST",
 *             "path"="dummy_dto_custom_post_without_output"
 *         },
 *         "post_with_output_no_persist"={
 *             "method"="POST",
 *             "output"=CustomOutputDto::class,
 *             "write"=false,
 *             "path"="dummy_dto_post_with_output_no_persist"
 *         }
 *     },
 *     itemOperations={"get", "custom_output"={"output"=CustomOutputDto::class, "method"="GET", "path"="dummy_dto_custom_output/{id}"}, "put", "delete"}
 * )
 */
class DummyDtoCustom
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column
     */
    public $lorem;

    /**
     * @var string
     *
     * @ORM\Column
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
