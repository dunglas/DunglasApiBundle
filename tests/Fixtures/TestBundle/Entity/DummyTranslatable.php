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
use ApiPlatform\Core\Translation\TranslatableInterface;
use ApiPlatform\Core\Translation\TranslationInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ApiResource(
 *     normalizationContext={"groups"="read", "jsonld_embed_context"=true},
 *     itemOperations={
 *         "get",
 *         "delete",
 *         "patch",
 *         "put"={
 *             "path"="dummy_translatables/{id}/{_locale}",
 *             "requirements"={"_locale"="en|fr|es"},
 *             "defaults"={"_locale"="en"},
 *             "swagger_context"={
 *                 "parameters"={
 *                     {"name"="_locale", "in"="path", "required"=true, "description"="The locale to use", "type"="string"}
 *                 }
 *             }
 *         }
 *     },
 *     collectionOperations={
 *         "get"={
 *             "path"="dummy_translatables/{_locale}",
 *             "requirements"={"_locale"="en|fr|es"},
 *             "defaults"={"_locale"="en"},
 *             "swagger_context"={
 *                 "parameters"={
 *                     {"name"="_locale", "in"="path", "required"=true, "description"="The locale to use", "type"="string"}
 *                 }
 *             }
 *         },
 *         "post"={
 *             "path"="dummy_translatables/{_locale}",
 *             "requirements"={"_locale"="en|fr|es"},
 *             "defaults"={"_locale"="en"},
 *             "swagger_context"={
 *                 "parameters"={
 *                     {"name"="_locale", "in"="path", "required"=true, "description"="The locale to use", "type"="string"}
 *                 }
 *             }
 *         }
 *     },
 *     translation={
 *         "class"=DummyTranslation::class,
 *         "allTranslationsClientEnabled"=true,
 *         "allTranslationsClientParameterName"="allTranslations"
 *     }
 * )
 * @ORM\Entity
 */
class DummyTranslatable implements TranslatableInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column
     * @Groups("read")
     * @ApiProperty(
     *     attributes={
     *         "jsonld_context"={
     *             "@container"=null
     *         }
     *     }
     * )
     */
    public $notTranslatedField;

    /**
     * @Groups("read")
     */
    public $name;

    /**
     * @Groups("read")
     */
    public $description;

    /**
     * @var Collection<DummyTranslation>
     *
     * @ORM\OneToMany(
     *     targetEntity="DummyTranslation",
     *     mappedBy="translatable",
     *     cascade={"persist", "remove"}
     * )
     *
     * @Ignore
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(DummyTranslation $translation): void
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->translatable = $this;
        }
    }

    public function removeTranslation(DummyTranslation $translation): void
    {
        $this->getTranslations()->removeElement($translation);
    }

    public function getResourceTranslation(string $locale): ?TranslationInterface
    {
        foreach ($this->getTranslations() as $translation) {
            if ($locale === $translation->getLocale()) {
                return $translation;
            }
        }

        return null;
    }

    public function getResourceTranslations(): iterable
    {
        return $this->getTranslations();
    }

    public function addResourceTranslation(TranslationInterface $translation): void
    {
        if ($translation instanceof DummyTranslation) {
            $this->addTranslation($translation);
        }
    }

    public function removeResourceTranslation(TranslationInterface $translation): void
    {
        if ($translation instanceof DummyTranslation) {
            $this->removeTranslation($translation);
        }
    }
}
