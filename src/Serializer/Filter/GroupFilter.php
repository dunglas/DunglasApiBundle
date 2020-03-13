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

namespace ApiPlatform\Core\Serializer\Filter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Group filter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class GroupFilter implements FilterInterface, SerializerContextFilterInterface
{
    private $overrideDefaultGroups;
    private $parameterName;
    private $whitelist;

    public function __construct(string $parameterName = 'groups', bool $overrideDefaultGroups = false, array $whitelist = null)
    {
        $this->overrideDefaultGroups = $overrideDefaultGroups;
        $this->parameterName = $parameterName;
        $this->whitelist = $whitelist;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated since API Platform 2.6, use {@see applyToSerializerContext()} method instead
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context)
    {
        trigger_deprecation('API Platform', '2.6', 'Using "%s()" method is deprecated, use "applyToSerializerContext()" method instead.', __METHOD__);

        if (\array_key_exists($this->parameterName, $commonAttribute = $request->attributes->get('_api_filters', []))) {
            $groups = $commonAttribute[$this->parameterName];
        } else {
            $groups = $request->query->get($this->parameterName);
        }

        if (!\is_array($groups)) {
            return;
        }

        if (null !== $this->whitelist) {
            $groups = array_intersect($this->whitelist, $groups);
        }

        if (!$this->overrideDefaultGroups && isset($context[AbstractNormalizer::GROUPS])) {
            $groups = array_merge((array) $context[AbstractNormalizer::GROUPS], $groups);
        }

        $context[AbstractNormalizer::GROUPS] = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToSerializerContext(string $resourceClass, string $operationName, bool $normalization, array $context, array &$serializerContext): void
    {
        if (\array_key_exists($this->parameterName, $commonAttribute = ($context['filters'] ?? []))) {
            $groups = $commonAttribute[$this->parameterName];
        } else {
            $groups = $context['request_query'][$this->parameterName] ?? null;
        }

        if (!\is_array($groups)) {
            return;
        }

        if (null !== $this->whitelist) {
            $groups = array_intersect($this->whitelist, $groups);
        }

        if (!$this->overrideDefaultGroups && isset($serializerContext[AbstractNormalizer::GROUPS])) {
            $groups = array_merge((array) $serializerContext[AbstractNormalizer::GROUPS], $groups);
        }

        $serializerContext[AbstractNormalizer::GROUPS] = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        return [
            "$this->parameterName[]" => [
                'property' => null,
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
            ],
        ];
    }
}
