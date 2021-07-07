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
use Symfony\Component\Validator\Constraints\Choice;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaChoiceRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Choice $constraint
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        $choices = [];

        if (\is_callable($constraint->callback)) {
            $choices = ($constraint->callback)();
        } elseif (\is_array($constraint->choices)) {
            $choices = $constraint->choices;
        }

        if (!$choices) {
            return [];
        }

        $restriction = [];

        if (!$constraint->multiple) {
            $restriction['enum'] = $choices;

            return $restriction;
        }

        $restriction['type'] = 'array';
        $restriction['items'] = ['type' => Type::BUILTIN_TYPE_STRING === $propertyMetadata->getType()->getBuiltinType() ? 'string' : 'number', 'enum' => $choices];

        if (null !== $constraint->min) {
            $restriction['minItems'] = $constraint->min;
        }

        if (null !== $constraint->max) {
            $restriction['maxItems'] = $constraint->max;
        }

        return $restriction;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        return $constraint instanceof Choice && null !== ($type = $propertyMetadata->getType()) && \in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_STRING, Type::BUILTIN_TYPE_INT, Type::BUILTIN_TYPE_FLOAT], true);
    }
}
