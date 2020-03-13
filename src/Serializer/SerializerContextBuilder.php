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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @deprecated since API Platform 2.6, use {@see \ApiPlatform\Core\Serializer\SerializerContextFactory} class instead
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    use ContextTrait;

    private $serializerContextFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerContextFactoryInterface $serializerContextFactory = null)
    {
        trigger_deprecation('API Platform', '2.6', 'Using "%s" class is deprecated, use "%s" class instead.', __CLASS__, SerializerContextFactory::class);

        $this->serializerContextFactory = $serializerContextFactory ?? new SerializerContextFactory($resourceMetadataFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        trigger_deprecation('API Platform', '2.6', 'Using "%s()" method is deprecated, use "%s::create()" method instead.', __METHOD__, SerializerContextFactory::class);

        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        $context = $this->addRequestContext($request, $attributes);
        $operationName = $this->getOperationNameFromContext($context);

        return $this->serializerContextFactory->create($context['resource_class'], $operationName, $normalization, $context);
    }
}
