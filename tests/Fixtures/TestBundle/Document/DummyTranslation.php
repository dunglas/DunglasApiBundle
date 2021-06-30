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

use ApiPlatform\Core\Translation\TranslatableInterface;
use ApiPlatform\Core\Translation\TranslationInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class DummyTranslation implements TranslationInterface
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    public $id;

    /**
     * @ODM\Field
     */
    public $locale;

    /**
     * @var string
     * @ODM\Field
     */
    public $name;

    /**
     * @var string
     * @ODM\Field
     */
    public $description;

    /**
     * @var DummyTranslatable
     *
     * @ODM\ReferenceOne(targetDocument=DummyTranslatable::class, inversedBy="translations")
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
