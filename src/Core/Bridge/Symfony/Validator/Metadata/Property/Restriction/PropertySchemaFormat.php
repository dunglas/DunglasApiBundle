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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Constraints\Ulid;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Uuid;

/**
 * Class PropertySchemaFormat.
 *
 * @author Andrii Penchuk penja7@gmail.com
 */
class PropertySchemaFormat implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        if ($constraint instanceof Email) {
            return ['format' => 'email'];
        }

        if ($constraint instanceof Url) {
            return ['format' => 'uri'];
        }

        if ($constraint instanceof Hostname) {
            return ['format' => 'hostname'];
        }

        if ($constraint instanceof Uuid) {
            return ['format' => 'uuid'];
        }

        if ($constraint instanceof Ulid) {
            return ['format' => 'ulid'];
        }

        if ($constraint instanceof Ip) {
            if ($constraint->version === $constraint::V4) {
                return ['format' => 'ipv4'];
            }

            return ['format' => 'ipv6'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        $schema = $propertyMetadata->getSchema();

        return empty($schema['format']) && ($constraint instanceof Email || $constraint instanceof Url || $constraint instanceof Hostname || $constraint instanceof Uuid || $constraint instanceof Ulid || $constraint instanceof Ip);
    }
}
