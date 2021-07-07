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

namespace ApiPlatform\Core\Bridge\Doctrine\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\HttpCache\PurgerInterface;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Purges responses containing modified entities from the proxy cache.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class PurgeHttpCacheListener
{
    private $purger;
    private $iriConverter;
    private $resourceClassResolver;
    private $propertyAccessor;
    private $tags = [];

    public function __construct(PurgerInterface $purger, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->purger = $purger;
        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * Collects tags from the previous and the current version of the updated entities to purge related documents.
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        $this->gatherResourceAndItemTags($object, true);

        $changeSet = $eventArgs->getEntityChangeSet();
        $associationMappings = $eventArgs->getEntityManager()->getClassMetadata(ClassUtils::getClass($eventArgs->getObject()))->getAssociationMappings();

        foreach ($changeSet as $key => $value) {
            if (!isset($associationMappings[$key])) {
                continue;
            }

            $this->addTagsFor($value[0]);
            $this->addTagsFor($value[1]);
        }
    }

    /**
     * Collects tags from inserted and deleted entities, including relations.
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->gatherResourceAndItemTags($entity, false);
            $this->gatherRelationTags($em, $entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->gatherResourceAndItemTags($entity, true);
            $this->gatherRelationTags($em, $entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->gatherResourceAndItemTags($entity, true);
            $this->gatherRelationTags($em, $entity);
        }
    }

    /**
     * Purges tags collected during this request, and clears the tag list.
     */
    public function postFlush(): void
    {
        if (empty($this->tags)) {
            return;
        }

        $this->purger->purge(array_values($this->tags));
        $this->tags = [];
    }

    private function gatherResourceAndItemTags($entity, bool $purgeItem): void
    {
        try {
            $resourceClass = $this->resourceClassResolver->getResourceClass($entity);
            $iri = $this->iriConverter->getIriFromResourceClass($resourceClass);
            $this->tags[$iri] = $iri;

            if ($purgeItem) {
                $this->addTagForItem($entity);
            }
        } catch (InvalidArgumentException $e) {
        }
    }

    private function gatherRelationTags(EntityManagerInterface $em, $entity): void
    {
        $associationMappings = $em->getClassMetadata(ClassUtils::getClass($entity))->getAssociationMappings();
        foreach (array_keys($associationMappings) as $property) {
            if ($this->propertyAccessor->isReadable($entity, $property)) {
                $this->addTagsFor($this->propertyAccessor->getValue($entity, $property));
            }
        }
    }

    private function addTagsFor($value): void
    {
        if (!$value || is_scalar($value)) {
            return;
        }

        if (!is_iterable($value)) {
            $this->addTagForItem($value);

            return;
        }

        if ($value instanceof PersistentCollection) {
            $value = clone $value;
        }

        foreach ($value as $v) {
            $this->addTagForItem($v);
        }
    }

    private function addTagForItem($value): void
    {
        try {
            //TODO: test if this is a resource class
            $iri = $this->iriConverter->getIriFromItem($value);
            $this->tags[$iri] = $iri;
        } catch (InvalidArgumentException $e) {
        } catch (RuntimeException $e) {
        }
    }
}
