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
use Symfony\Component\Validator\Constraints\Length;

/**
 * Class PropertySchemaLengthRestrictions.
 *
 * @author Andrii Penchuk penja7@gmail.com
 */
class PropertySchemaLengthRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        $restriction = [];

        if (isset($constraint->min)) {
            $restriction['minLength'] = (int) $constraint->min;
        }

        if (isset($constraint->max)) {
            $restriction['maxLength'] = (int) $constraint->max;
        }

        return $restriction;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        return $constraint instanceof Length && null !== ($type = $propertyMetadata->getType()) && Type::BUILTIN_TYPE_STRING === $type->getBuiltinType();
    }
}
