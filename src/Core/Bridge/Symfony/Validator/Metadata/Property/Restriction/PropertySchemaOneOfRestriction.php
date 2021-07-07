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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PropertySchemaOneOfRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * @var iterable<PropertySchemaRestrictionMetadataInterface>
     */
    private $restrictionsMetadata;

    /**
     * @param iterable<PropertySchemaRestrictionMetadataInterface> $restrictionsMetadata
     */
    public function __construct(iterable $restrictionsMetadata = [])
    {
        $this->restrictionsMetadata = $restrictionsMetadata;
    }

    /**
     * {@inheritdoc}
     *
     * @param AtLeastOneOf $constraint
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        $oneOfConstraints = $constraint->getNestedContraints();
        $oneOfRestrictions = [];

        foreach ($oneOfConstraints as $oneOfConstraint) {
            foreach ($this->restrictionsMetadata as $restrictionMetadata) {
                if ($restrictionMetadata->supports($oneOfConstraint, $propertyMetadata) && !empty($oneOfRestriction = $restrictionMetadata->create($oneOfConstraint, $propertyMetadata))) {
                    $oneOfRestrictions[] = $oneOfRestriction;
                }
            }
        }

        if (!empty($oneOfRestrictions)) {
            return ['oneOf' => $oneOfRestrictions];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        return $constraint instanceof AtLeastOneOf;
    }
}
