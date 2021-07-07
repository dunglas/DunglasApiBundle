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

namespace ApiPlatform\Core\GraphQl\Type;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a type has not been found in the types container.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypeNotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
    private $typeId;

    public function __construct(string $message, string $typeId)
    {
        $this->typeId = $typeId;

        parent::__construct($message);
    }

    /**
     * Returns the type identifier causing this exception.
     */
    public function getTypeId(): string
    {
        return $this->typeId;
    }
}
