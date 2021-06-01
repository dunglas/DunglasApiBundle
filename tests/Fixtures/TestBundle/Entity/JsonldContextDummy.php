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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Jsonld Context Dummy.
 *
 * @ApiResource
 * @ORM\Entity
 */
class JsonldContextDummy
{
    /**
     * @var int The id
     *
     * @ApiProperty(identifier=true)
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The dummy person
     *
     * @ApiProperty(
     *     attributes={
     *         "jsonld_context"={
     *             "@id"="http://example.com/id",
     *             "@var"="@id",
     *             "foo"="bar"
     *         }
     *     },
     * )
     */
    private $person;

    public function getId()
    {
        return $this->id;
    }

    public function setPerson($person)
    {
        $this->person = $person;
    }

    public function getPerson()
    {
        return $this->person;
    }
}
