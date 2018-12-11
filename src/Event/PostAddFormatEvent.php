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

namespace ApiPlatform\Core\Event;

use Symfony\Component\EventDispatcher\Event;

final class PostAddFormatEvent extends Event
{
    private $formats;

    public function __construct(array $formats)
    {
        $this->formats = $formats;
    }

    public function getFormats(): array
    {
        return $this->formats;
    }

    public function setFormats(array $formats): void
    {
        $this->formats = $formats;
    }
}