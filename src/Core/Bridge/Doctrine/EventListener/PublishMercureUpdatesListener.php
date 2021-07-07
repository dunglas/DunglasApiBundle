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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Symfony\Messenger\DispatchTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface as GraphQlMercureSubscriptionIriGeneratorInterface;
use ApiPlatform\Core\GraphQl\Subscription\SubscriptionManagerInterface as GraphQlSubscriptionManagerInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs as MongoDbOdmOnFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs as OrmOnFlushEventArgs;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Publishes resources updates to the Mercure hub.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class PublishMercureUpdatesListener
{
    use DispatchTrait;
    use ResourceClassInfoTrait;
    private const ALLOWED_KEYS = [
        'topics' => true,
        'data' => true,
        'private' => true,
        'id' => true,
        'type' => true,
        'retry' => true,
        'normalization_context' => true,
        'hub' => true,
        'enable_async_update' => true,
    ];

    private $iriConverter;
    private $serializer;
    private $hubRegistry;
    private $expressionLanguage;
    private $createdObjects;
    private $updatedObjects;
    private $deletedObjects;
    private $formats;
    private $graphQlSubscriptionManager;
    private $graphQlMercureSubscriptionIriGenerator;

    /**
     * @param array<string, string[]|string> $formats
     * @param HubRegistry|callable           $hubRegistry
     */
    public function __construct(ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter, ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerInterface $serializer, array $formats, MessageBusInterface $messageBus = null, $hubRegistry = null, ?GraphQlSubscriptionManagerInterface $graphQlSubscriptionManager = null, ?GraphQlMercureSubscriptionIriGeneratorInterface $graphQlMercureSubscriptionIriGenerator = null, ExpressionLanguage $expressionLanguage = null)
    {
        if (null === $messageBus && null === $hubRegistry) {
            throw new InvalidArgumentException('A message bus or a hub registry must be provided.');
        }

        $this->resourceClassResolver = $resourceClassResolver;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->serializer = $serializer;
        $this->formats = $formats;
        $this->messageBus = $messageBus;
        $this->hubRegistry = $hubRegistry;
        $this->expressionLanguage = $expressionLanguage ?? (class_exists(ExpressionLanguage::class) ? new ExpressionLanguage() : null);
        $this->graphQlSubscriptionManager = $graphQlSubscriptionManager;
        $this->graphQlMercureSubscriptionIriGenerator = $graphQlMercureSubscriptionIriGenerator;
        $this->reset();

        $rawurlencode = ExpressionFunction::fromPhp('rawurlencode', 'escape');
        $this->expressionLanguage->addFunction($rawurlencode);

        $this->expressionLanguage->addFunction(
            new ExpressionFunction('iri', static function (string $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL): string {
                return sprintf('iri(%s, %d)', $apiResource, $referenceType);
            }, static function (array $arguments, $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL) use ($iriConverter): string {
                return $iriConverter->getIriFromItem($apiResource, $referenceType);
            })
        );
    }

    /**
     * Collects created, updated and deleted objects.
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if ($eventArgs instanceof OrmOnFlushEventArgs) {
            $uow = $eventArgs->getEntityManager()->getUnitOfWork();
        } elseif ($eventArgs instanceof MongoDbOdmOnFlushEventArgs) {
            $uow = $eventArgs->getDocumentManager()->getUnitOfWork();
        } else {
            return;
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityInsertions' : 'getScheduledDocumentInsertions';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, 'createdObjects');
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityUpdates' : 'getScheduledDocumentUpdates';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, 'updatedObjects');
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityDeletions' : 'getScheduledDocumentDeletions';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, 'deletedObjects');
        }
    }

    /**
     * Publishes updates for changes collected on flush, and resets the store.
     */
    public function postFlush(): void
    {
        try {
            foreach ($this->createdObjects as $object) {
                $this->publishUpdate($object, $this->createdObjects[$object], 'create');
            }

            foreach ($this->updatedObjects as $object) {
                $this->publishUpdate($object, $this->updatedObjects[$object], 'update');
            }

            foreach ($this->deletedObjects as $object) {
                $this->publishUpdate($object, $this->deletedObjects[$object], 'delete');
            }
        } finally {
            $this->reset();
        }
    }

    private function reset(): void
    {
        $this->createdObjects = new \SplObjectStorage();
        $this->updatedObjects = new \SplObjectStorage();
        $this->deletedObjects = new \SplObjectStorage();
    }

    /**
     * @param object $object
     */
    private function storeObjectToPublish($object, string $property): void
    {
        if (null === $resourceClass = $this->getResourceClass($object)) {
            return;
        }

        $options = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('mercure', false);

        if (\is_string($options)) {
            if (null === $this->expressionLanguage) {
                throw new RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
            }

            $options = $this->expressionLanguage->evaluate($options, ['object' => $object]);
        }

        if (false === $options) {
            return;
        }

        if (true === $options) {
            $options = [];
        }

        if (!\is_array($options)) {
            throw new InvalidArgumentException(sprintf('The value of the "mercure" attribute of the "%s" resource class must be a boolean, an array of options or an expression returning this array, "%s" given.', $resourceClass, \gettype($options)));
        }

        foreach ($options as $key => $value) {
            if (0 === $key) {
                if (method_exists(Update::class, 'isPrivate')) {
                    throw new \InvalidArgumentException('Targets do not exist anymore since Mercure 0.10. Mark the update as private instead or downgrade the Mercure Component to version 0.3');
                }

                @trigger_error('Targets do not exist anymore since Mercure 0.10. Mark the update as private instead.', \E_USER_DEPRECATED);
                break;
            }

            if (!isset(self::ALLOWED_KEYS[$key])) {
                throw new InvalidArgumentException(sprintf('The option "%s" set in the "mercure" attribute of the "%s" resource does not exist. Existing options: "%s"', $key, $resourceClass, implode('", "', self::ALLOWED_KEYS)));
            }

            if ('hub' === $key && !$this->hubRegistry instanceof HubRegistry) {
                throw new InvalidArgumentException(sprintf('The option "hub" of the "mercure" attribute cannot be set on the "%s" resource . Try running "composer require symfony/mercure:^0.5".', $resourceClass));
            }
        }

        $options['enable_async_update'] = $options['enable_async_update'] ?? true;

        if ($options['topics'] ?? false) {
            $topics = [];
            foreach ((array) $options['topics'] as $topic) {
                if (!\is_string($topic)) {
                    $topics[] = $topic;
                    continue;
                }

                if (0 !== strpos($topic, '@=')) {
                    $topics[] = $topic;
                    continue;
                }

                if (null === $this->expressionLanguage) {
                    throw new \LogicException('The "@=" expression syntax cannot be used without the Expression Language component. Try running "composer require symfony/expression-language".');
                }

                $topics[] = $this->expressionLanguage->evaluate(substr($topic, 2), ['object' => $object]);
            }

            $options['topics'] = $topics;
        }

        if ('deletedObjects' === $property) {
            $this->deletedObjects[(object) [
                'id' => $this->iriConverter->getIriFromItem($object),
                'iri' => $this->iriConverter->getIriFromItem($object, UrlGeneratorInterface::ABS_URL),
            ]] = $options;

            return;
        }

        $this->{$property}[$object] = $options;
    }

    /**
     * @param object $object
     */
    private function publishUpdate($object, array $options, string $type): void
    {
        if ($object instanceof \stdClass) {
            // By convention, if the object has been deleted, we send only its IRI.
            // This may change in the feature, because it's not JSON Merge Patch compliant,
            // and I'm not a fond of this approach.
            $iri = $options['topics'] ?? $object->iri;
            /** @var string $data */
            $data = json_encode(['@id' => $object->id]);
        } else {
            $resourceClass = $this->getObjectClass($object);
            $context = $options['normalization_context'] ?? $this->resourceMetadataFactory->create($resourceClass)->getAttribute('normalization_context', []);

            $iri = $options['topics'] ?? $this->iriConverter->getIriFromItem($object, UrlGeneratorInterface::ABS_URL);
            $data = $options['data'] ?? $this->serializer->serialize($object, key($this->formats), $context);
        }

        $updates = array_merge([$this->buildUpdate($iri, $data, $options)], $this->getGraphQlSubscriptionUpdates($object, $options, $type));

        foreach ($updates as $update) {
            if ($options['enable_async_update'] && $this->messageBus) {
                $this->dispatch($update);
                continue;
            }

            $this->hubRegistry instanceof HubRegistry ? $this->hubRegistry->getHub($options['hub'] ?? null)->publish($update) : ($this->hubRegistry)($update);
        }
    }

    /**
     * @param object $object
     *
     * @return Update[]
     */
    private function getGraphQlSubscriptionUpdates($object, array $options, string $type): array
    {
        if ('update' !== $type || !$this->graphQlSubscriptionManager || !$this->graphQlMercureSubscriptionIriGenerator) {
            return [];
        }

        $payloads = $this->graphQlSubscriptionManager->getPushPayloads($object);

        $updates = [];
        foreach ($payloads as [$subscriptionId, $data]) {
            $updates[] = $this->buildUpdate(
                $this->graphQlMercureSubscriptionIriGenerator->generateTopicIri($subscriptionId),
                (string) (new JsonResponse($data))->getContent(),
                $options
            );
        }

        return $updates;
    }

    /**
     * @param string|string[] $iri
     */
    private function buildUpdate($iri, string $data, array $options): Update
    {
        if (method_exists(Update::class, 'isPrivate')) {
            return new Update($iri, $data, $options['private'] ?? false, $options['id'] ?? null, $options['type'] ?? null, $options['retry'] ?? null);
        }

        // Mercure Component < 0.4.
        /* @phpstan-ignore-next-line */
        return new Update($iri, $data, $options);
    }
}
