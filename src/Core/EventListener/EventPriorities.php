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

namespace ApiPlatform\Core\EventListener;

/**
 * Constants for common priorities.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EventPriorities
{
    // kernel.request
    public const PRE_READ = 5;
    public const POST_READ = 3;
    public const PRE_DESERIALIZE = 3;
    public const POST_DESERIALIZE = 1;
    // kernel.view
    public const PRE_VALIDATE = 65;
    public const POST_VALIDATE = 63;
    public const PRE_WRITE = 33;
    public const POST_WRITE = 31;
    public const PRE_SERIALIZE = 17;
    public const POST_SERIALIZE = 15;
    public const PRE_RESPOND = 9;
    // kernel.response
    public const POST_RESPOND = 0;
}
