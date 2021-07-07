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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\ArgumentResolver;

use ApiPlatform\Core\EventListener\ToggleableDeserializationTrait;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class PayloadArgumentResolver implements ArgumentValueResolverInterface
{
    use ToggleableDeserializationTrait;

    private $serializationContextBuilder;

    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        SerializerContextBuilderInterface $serializationContextBuilder
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->serializationContextBuilder = $serializationContextBuilder;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->isVariadic()) {
            return false;
        }

        $class = $argument->getType();

        if (null === $class) {
            return false;
        }

        if (null === $request->attributes->get('data')) {
            return false;
        }

        $inputClass = $this->getExpectedInputClass($request);

        if (null === $inputClass) {
            return false;
        }

        return $inputClass === $class || is_subclass_of($inputClass, $class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        yield $request->attributes->get('data');
    }

    private function getExpectedInputClass(Request $request): ?string
    {
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if (!$this->isRequestToDeserialize($request, $attributes)) {
            return null;
        }

        $context = $this->serializationContextBuilder->createFromRequest($request, false, $attributes);

        return $context['input'] ?? $context['resource_class'];
    }
}
