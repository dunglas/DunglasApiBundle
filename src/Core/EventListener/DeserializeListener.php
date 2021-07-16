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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Api\FormatMatcher;
use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Updates the entity retrieved by the data provider with data contained in the request body.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DeserializeListener
{
    use OperationRequestInitiatorTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'deserialize';

    private $serializer;
    private $serializerContextBuilder;
    private $formats;
    private $formatsProvider;
    private $resourceCollectionMetadataFactory;
    private $legacyResourceMetadataCollectionMetadataFactory;

    /**
     * @param ResourceMetadataFactoryInterface|FormatsProviderInterface|array $resourceMetadataFactory
     */
    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder, $resourceMetadataFactory, ResourceMetadataFactoryInterface $legacyResourceMetadataFactory = null)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->resourceMetadataFactory = ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface || $resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) ? $resourceMetadataFactory : $legacyResourceMetadataFactory;

        if ($resourceMetadataFactory) {
            if (!$resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                @trigger_error(sprintf('Passing an array or an instance of "%s" as 3rd parameter of the constructor of "%s" is deprecated since API Platform 2.5, pass an instance of "%s" instead', FormatsProviderInterface::class, __CLASS__, ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
            }

            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0.', ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface ? $resourceMetadataFactory : null;

        if (\is_array($resourceMetadataFactory)) {
            $this->formats = $resourceMetadataFactory;
        } elseif ($resourceMetadataFactory instanceof FormatsProviderInterface) {
            $this->formatsProvider = $resourceMetadataFactory;
        }
    }

    /**
     * Deserializes the data sent in the requested format.
     *
     * @throws UnsupportedMediaTypeHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();

        $operation = $this->initializeOperation($request);

        if (
            'DELETE' === $method
            || $request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface &&
            (!$operation || !$operation->canDeserialize() || !$attributes['receive'])
        ) {
            return;
        }

        if (!$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface && (
            !$attributes['receive']
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        )) {
            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);

        $formats = $operation->getInputFormats() ?? null;

        if (!$formats) {
            // BC check to be removed in 3.0
            if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                trigger_deprecation('When using a "route_name", be sure to define the "_api_operation" route defaults as we will not rely on metadata in API Platform 3.0.', \E_USER_DEPRECATED);
                $formats = $this->resourceMetadataFactory
                    ->create($attributes['resource_class'])
                    ->getOperationAttribute($attributes, 'input_formats', [], true);
            } elseif ($this->formatsProvider instanceof FormatsProviderInterface) {
                $formats = $this->formatsProvider->getFormatsFromAttributes($attributes);
            } else {
                $formats = $this->formats;
            }
        }

        $format = $this->getFormat($request, $formats);
        $data = $request->attributes->get('data');
        if (null !== $data) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }

        $request->attributes->set(
            'data',
            $this->serializer->deserialize($request->getContent(), $context['resource_class'], $format, $context)
        );
    }

    /**
     * Extracts the format from the Content-Type header and check that it is supported.
     *
     * @throws UnsupportedMediaTypeHttpException
     */
    private function getFormat(Request $request, array $formats): string
    {
        /**
         * @var string|null
         */
        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType) {
            throw new UnsupportedMediaTypeHttpException('The "Content-Type" header must exist.');
        }

        $formatMatcher = new FormatMatcher($formats);
        $format = $formatMatcher->getFormat($contentType);
        if (null === $format) {
            $supportedMimeTypes = [];
            foreach ($formats as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $supportedMimeTypes[] = $mimeType;
                }
            }

            throw new UnsupportedMediaTypeHttpException(sprintf('The content-type "%s" is not supported. Supported MIME types are "%s".', $contentType, implode('", "', $supportedMimeTypes)));
        }

        return $format;
    }
}
