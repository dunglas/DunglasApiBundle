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
use Symfony\Component\Validator\Constraints\LessThan;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaLessThanRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param LessThan $constraint
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        return [
            'maximum' => $constraint->value,
            'exclusiveMaximum' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        return $constraint instanceof LessThan && is_numeric($constraint->value) && null !== ($type = $propertyMetadata->getType()) && \in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_INT, Type::BUILTIN_TYPE_FLOAT], true);
    }
}
