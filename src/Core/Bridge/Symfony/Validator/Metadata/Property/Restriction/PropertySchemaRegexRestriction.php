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
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class PropertySchemaRegexRestriction.
 *
 * @author Andrii Penchuk penja7@gmail.com
 */
class PropertySchemaRegexRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        return $constraint instanceof Regex && $constraint->getHtmlPattern() ? ['pattern' => '^('.$constraint->getHtmlPattern().')$'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        return $constraint instanceof Regex && $constraint->match;
    }
}
