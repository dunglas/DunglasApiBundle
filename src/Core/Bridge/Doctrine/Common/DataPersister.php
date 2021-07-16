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

namespace ApiPlatform\Core\Bridge\Doctrine\Common;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager as DoctrineObjectManager;

/**
 * Data persister for Doctrine.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class DataPersister implements ContextAwareDataPersisterInterface
{
    use ClassInfoTrait;

    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        return null !== $this->getManager($data);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = [])
    {
        if (!$manager = $this->getManager($data)) {
            return $data;
        }

        if (!$manager->contains($data) || $this->isDeferredExplicit($manager, $data)) {
            $manager->persist($data);
        }

        $manager->flush();
        $manager->refresh($data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = [])
    {
        if (!$manager = $this->getManager($data)) {
            return;
        }

        $manager->remove($data);
        $manager->flush();
    }

    /**
     * Gets the Doctrine object manager associated with given data.
     */
    private function getManager($data): ?DoctrineObjectManager
    {
        return \is_object($data) ? $this->managerRegistry->getManagerForClass($this->getObjectClass($data)) : null;
    }

    /**
     * Checks if doctrine does not manage data automatically.
     */
    private function isDeferredExplicit(DoctrineObjectManager $manager, $data): bool
    {
        $classMetadata = $manager->getClassMetadata($this->getObjectClass($data));
        if (($classMetadata instanceof ClassMetadataInfo || $classMetadata instanceof ClassMetadata) && method_exists($classMetadata, 'isChangeTrackingDeferredExplicit')) {
            return $classMetadata->isChangeTrackingDeferredExplicit();
        }

        return false;
    }
}
