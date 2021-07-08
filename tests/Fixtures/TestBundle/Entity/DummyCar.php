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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ApiResource(
 *     itemOperations={"get"={"swagger_context"={"tags"={}}, "openapi_context"={"tags"={}}}, "put", "delete"},
 *     attributes={
 *         "sunset"="2050-01-01",
 *         "normalization_context"={"groups"={"colors"}}
 *     }
 * )
 * @ORM\Entity
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(PropertyFilter::class, arguments={"parameterName"="foobar"})
 * @ApiFilter(GroupFilter::class, arguments={"parameterName"="foobargroups"})
 * @ApiFilter(GroupFilter::class, arguments={"parameterName"="foobargroups_override"}, id="override")
 */
class DummyCar
{
    /**
     * @var DummyCarIdentifier The entity Id
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="DummyCarIdentifier", cascade={"persist"})
     */
    private $id;

    /**
     * @var mixed Something else
     *
     * @ORM\OneToMany(targetEntity="DummyCarColor", mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     * @Serializer\SerializedName("couleurs")
     * @ApiFilter(SearchFilter::class, properties={"colors.prop"="ipartial", "colors"="exact"})
     */
    private $colors;

    /**
     * @var mixed Something else
     *
     * @ORM\OneToMany(targetEntity="DummyCarColor", mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    private $secondColors;

    /**
     * @var mixed Something else
     *
     * @ORM\OneToMany(targetEntity="DummyCarColor", mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    private $thirdColors;

    /**
     * @var mixed Something else
     *
     * @ORM\ManyToMany(targetEntity="UuidIdentifierDummy", indexBy="uuid")
     * * @ORM\JoinTable(name="uuid_cars",
     *     joinColumns={@ORM\JoinColumn(name="car_id", referencedColumnName="id_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="uuid_uuid", referencedColumnName="uuid")}
     * )
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @ApiFilter(SearchFilter::class, strategy="partial")
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $canSell;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $availableAt;

    /**
     * @var string
     *
     * @Serializer\Groups({"colors"})
     * @Serializer\SerializedName("carBrand")
     *
     * @ORM\Column
     */
    private $brand = 'DummyBrand';

    public function __construct()
    {
        $this->id = new DummyCarIdentifier();
        $this->colors = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getColors()
    {
        return $this->colors;
    }

    public function setColors($colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    public function getSecondColors()
    {
        return $this->secondColors;
    }

    public function setSecondColors($secondColors)
    {
        $this->secondColors = $secondColors;
    }

    public function getThirdColors()
    {
        return $this->thirdColors;
    }

    public function setThirdColors($thirdColors)
    {
        $this->thirdColors = $thirdColors;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getCanSell(): bool
    {
        return $this->canSell;
    }

    public function setCanSell(bool $canSell)
    {
        $this->canSell = $canSell;
    }

    public function getAvailableAt(): \DateTime
    {
        return $this->availableAt;
    }

    public function setAvailableAt(\DateTime $availableAt)
    {
        $this->availableAt = $availableAt;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }
}
