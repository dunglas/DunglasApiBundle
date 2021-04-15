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

namespace ApiPlatform\Core\Validator;

use ApiPlatform\Core\Validator\Exception\ValidationException;
use Symfony\Component\Validator\Constraint;

/**
 * Validates an item.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
interface ConstraintValidatorInterface extends ValidatorInterface
{
    /**
     * Validates an item with an optional set of constraints.
     *
     * @param Constraint[]|null $constraints
     *
     * @throws ValidationException
     */
    public function validate($data, array $context = [], ?array $constraints = null);
}
