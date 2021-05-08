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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"chicago"}},
 *         "denormalization_context"={"groups"={"chicago"}},
 *     },
 *     itemOperations={
 *         "get",
 *         "patch"={"input_formats"={"json"={"application/merge-patch+json"}, "jsonapi"}}
 *     }
 * )
 * @ORM\Entity
 */
class PatchDummyRelation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
     * @Groups({"chicago"})
     */
    protected $related;

    /**
     * @ORM\OneToMany(targetEntity="RelatedDummy", mappedBy="relatedPatchDummy")
     * @Groups({"chicago"})
     */
    protected $relatedDummies;

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
    }

    public function getRelated()
    {
        return $this->related;
    }

    public function setRelated(RelatedDummy $relatedDummy)
    {
        $this->related = $relatedDummy;
    }

    /**
     * @return Collection|RelatedDummy[]
     */
    public function getRelatedDummies(): Collection
    {
        return $this->relatedDummies;
    }

    public function addRelatedDummy(RelatedDummy $relatedDummy): self
    {
        if (!$this->relatedDummies->contains($relatedDummy)) {
            $this->relatedDummies[] = $relatedDummy;
            $relatedDummy->relatedPatchDummy = $this;
        }

        return $this;
    }

    public function removeRelatedDummy(RelatedDummy $relatedDummy): self
    {
        $this->relatedDummies->removeElement($relatedDummy);

        return $this;
    }
}
