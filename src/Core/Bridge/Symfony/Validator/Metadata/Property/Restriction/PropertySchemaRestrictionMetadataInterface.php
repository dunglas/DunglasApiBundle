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

/**
 * Interface PropertySchemaRestrictionsInterface.
 *
 * @author Andrii Penchuk penja7@gmail.com
 */
interface PropertySchemaRestrictionMetadataInterface
{
    /**
     * Creates json schema restrictions based on the validation constraints.
     *
     * @param Constraint       $constraint       The validation constraint
     * @param PropertyMetadata $propertyMetadata The property metadata
     *
     * @return array The array of restrictions
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array;

    /**
     * Is the constraint supported by the schema restriction?
     *
     * @param Constraint       $constraint       The validation constraint
     * @param PropertyMetadata $propertyMetadata The property metadata
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool;
}
