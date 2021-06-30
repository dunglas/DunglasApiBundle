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

use ApiPlatform\Core\Translation\TranslatableInterface;
use ApiPlatform\Core\Translation\TranslationInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class DummyTranslation implements TranslationInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column
     */
    public $locale;

    /**
     * @ORM\Column
     */
    public $name;

    /**
     * @ORM\Column
     */
    public $description;

    /**
     * @var DummyTranslatable
     *
     * @ORM\ManyToOne(targetEntity="DummyTranslatable", inversedBy="translations")
     * @ORM\JoinColumn(name="translatable_id", referencedColumnName="id", onDelete="CASCADE")
     */
    public $translatable;

    public function getTranslatableResource(): TranslatableInterface
    {
        return $this->translatable;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
