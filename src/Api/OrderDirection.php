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

namespace ApiPlatform\Core\Api;

final class OrderDirection implements EnumInterface
{
    const ASC = 'ASC';
    const DESC = 'DESC';

    public static function getValues(): array
    {
        return [self::ASC, self::DESC];
    }

    public static function getName(): string
    {
        return 'OrderDirection';
    }
}
