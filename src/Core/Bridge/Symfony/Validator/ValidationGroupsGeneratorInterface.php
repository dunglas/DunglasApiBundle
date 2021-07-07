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

namespace ApiPlatform\Core\Bridge\Symfony\Validator;

use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Generates validation groups for an object.
 *
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
interface ValidationGroupsGeneratorInterface
{
    /**
     * @param object $object
     *
     * @return GroupSequence|string[]
     */
    public function __invoke($object);
}
