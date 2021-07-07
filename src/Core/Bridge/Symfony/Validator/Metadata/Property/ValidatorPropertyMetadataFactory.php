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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property;

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\CardScheme;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Currency;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Isbn;
use Symfony\Component\Validator\Constraints\Issn;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Time;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface as ValidatorClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface as ValidatorMetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface as ValidatorPropertyMetadataInterface;

/**
 * Decorates a metadata loader using the validator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidatorPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    /**
     * @var string[] A list of constraint classes making the entity required
     */
    public const REQUIRED_CONSTRAINTS = [NotBlank::class, NotNull::class];

    public const SCHEMA_MAPPED_CONSTRAINTS = [
        Url::class => 'http://schema.org/url',
        Email::class => 'http://schema.org/email',
        Uuid::class => 'http://schema.org/identifier',
        CardScheme::class => 'http://schema.org/identifier',
        Bic::class => 'http://schema.org/identifier',
        Iban::class => 'http://schema.org/identifier',
        Date::class => 'http://schema.org/Date',
        DateTime::class => 'http://schema.org/DateTime',
        Time::class => 'http://schema.org/Time',
        Image::class => 'http://schema.org/image',
        File::class => 'http://schema.org/MediaObject',
        Currency::class => 'http://schema.org/priceCurrency',
        Isbn::class => 'http://schema.org/isbn',
        Issn::class => 'http://schema.org/issn',
    ];

    private $decorated;
    private $validatorMetadataFactory;
    /**
     * @var iterable<PropertySchemaRestrictionMetadataInterface>
     */
    private $restrictionsMetadata;

    /**
     * @param PropertySchemaRestrictionMetadataInterface[] $restrictionsMetadata
     */
    public function __construct(ValidatorMetadataFactoryInterface $validatorMetadataFactory, PropertyMetadataFactoryInterface $decorated, iterable $restrictionsMetadata = [])
    {
        $this->validatorMetadataFactory = $validatorMetadataFactory;
        $this->decorated = $decorated;
        $this->restrictionsMetadata = $restrictionsMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        $required = $propertyMetadata->isRequired();
        $iri = $propertyMetadata->getIri();
        $schema = $propertyMetadata->getSchema();

        if (null !== $required && null !== $iri && null !== $schema) {
            return $propertyMetadata;
        }

        $validatorClassMetadata = $this->validatorMetadataFactory->getMetadataFor($resourceClass);

        if (!$validatorClassMetadata instanceof ValidatorClassMetadataInterface) {
            throw new \UnexpectedValueException(sprintf('Validator class metadata expected to be of type "%s".', ValidatorClassMetadataInterface::class));
        }

        $validationGroups = $this->getValidationGroups($validatorClassMetadata, $options);
        $restrictions = [];

        foreach ($validatorClassMetadata->getPropertyMetadata($property) as $validatorPropertyMetadata) {
            foreach ($this->getPropertyConstraints($validatorPropertyMetadata, $validationGroups) as $constraint) {
                if (null === $required && $this->isRequired($constraint)) {
                    $required = true;
                }

                if (null === $iri) {
                    $iri = self::SCHEMA_MAPPED_CONSTRAINTS[\get_class($constraint)] ?? null;
                }

                foreach ($this->restrictionsMetadata as $restrictionMetadata) {
                    if ($restrictionMetadata->supports($constraint, $propertyMetadata)) {
                        $restrictions[] = $restrictionMetadata->create($constraint, $propertyMetadata);
                    }
                }
            }
        }

        $propertyMetadata = $propertyMetadata->withIri($iri)->withRequired($required ?? false);

        if (!empty($restrictions)) {
            if (null === $schema) {
                $schema = [];
            }

            $schema += array_merge(...$restrictions);
            $propertyMetadata = $propertyMetadata->withSchema($schema);
        }

        return $propertyMetadata;
    }

    /**
     * Returns the list of validation groups.
     */
    private function getValidationGroups(ValidatorClassMetadataInterface $classMetadata, array $options): array
    {
        if (isset($options['validation_groups'])) {
            return $options['validation_groups'];
        }

        if (!method_exists($classMetadata, 'getDefaultGroup')) {
            throw new \UnexpectedValueException(sprintf('Validator class metadata expected to have method "%s".', 'getDefaultGroup'));
        }

        return [$classMetadata->getDefaultGroup()];
    }

    /**
     * Tests if the property is required because of its validation groups.
     */
    private function getPropertyConstraints(
        ValidatorPropertyMetadataInterface $validatorPropertyMetadata,
        array $groups
    ): array {
        $constraints = [];

        foreach ($groups as $validationGroup) {
            if (!\is_string($validationGroup)) {
                continue;
            }

            foreach ($validatorPropertyMetadata->findConstraints($validationGroup) as $propertyConstraint) {
                if ($propertyConstraint instanceof Sequentially || $propertyConstraint instanceof Compound) {
                    $constraints[] = $propertyConstraint->getNestedContraints();
                } else {
                    $constraints[] = [$propertyConstraint];
                }
            }
        }

        return array_merge([], ...$constraints);
    }

    /**
     * Is this constraint making the related property required?
     */
    private function isRequired(Constraint $constraint): bool
    {
        foreach (self::REQUIRED_CONSTRAINTS as $requiredConstraint) {
            if ($constraint instanceof $requiredConstraint) {
                return true;
            }
        }

        return false;
    }
}
