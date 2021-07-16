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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Use Doctrine metadata to populate the identifier property.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DoctrineMongoDbOdmPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $decorated;
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry, PropertyMetadataFactoryInterface $decorated)
    {
        $this->managerRegistry = $managerRegistry;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        if (null !== $propertyMetadata->isIdentifier()) {
            return $propertyMetadata;
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager instanceof DocumentManager) {
            return $propertyMetadata;
        }

        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        $identifiers = $doctrineClassMetadata->getIdentifier();

        foreach ($identifiers as $identifier) {
            if ($identifier === $property) {
                $propertyMetadata = $propertyMetadata->withIdentifier(true);

                if (null !== $propertyMetadata->isWritable()) {
                    break;
                }

                $propertyMetadata = $propertyMetadata->withWritable(false);

                break;
            }
        }

        if (null === $propertyMetadata->isIdentifier()) {
            $propertyMetadata = $propertyMetadata->withIdentifier(false);
        }

        return $propertyMetadata;
    }
}
