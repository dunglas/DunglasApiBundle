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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaRangeRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        $restriction = [];

        if (isset($constraint->min) && is_numeric($constraint->min)) {
            $restriction['minimum'] = $constraint->min;
        }

        if (isset($constraint->max) && is_numeric($constraint->max)) {
            $restriction['maximum'] = $constraint->max;
        }

        return $restriction;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        return $constraint instanceof Range && null !== ($type = $propertyMetadata->getType()) && \in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_INT, Type::BUILTIN_TYPE_FLOAT], true);
    }
}
