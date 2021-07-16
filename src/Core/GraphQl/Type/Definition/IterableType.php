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

namespace ApiPlatform\Core\GraphQl\Type\Definition;

use GraphQL\Error\Error;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

if (\PHP_VERSION_ID >= 70200) {
    trait IterableTypeParseLiteralTrait
    {
        /**
         * {@inheritdoc}
         *
         * @param ObjectValueNode|ListValueNode|IntValueNode|FloatValueNode|StringValueNode|BooleanValueNode|NullValueNode $valueNode
         */
        public function parseLiteral(/*Node */ $valueNode, ?array $variables = null)
        {
            if ($valueNode instanceof ObjectValueNode || $valueNode instanceof ListValueNode) {
                return $this->parseIterableLiteral($valueNode);
            }

            // Intentionally without message, as all information already in wrapped Exception
            throw new \Exception();
        }
    }
} else {
    trait IterableTypeParseLiteralTrait
    {
        /**
         * {@inheritdoc}
         *
         * @param ObjectValueNode|ListValueNode|IntValueNode|FloatValueNode|StringValueNode|BooleanValueNode|NullValueNode $valueNode
         */
        public function parseLiteral(Node $valueNode, ?array $variables = null)
        {
            if ($valueNode instanceof ObjectValueNode || $valueNode instanceof ListValueNode) {
                return $this->parseIterableLiteral($valueNode);
            }

            // Intentionally without message, as all information already in wrapped Exception
            throw new \Exception();
        }
    }
}

/**
 * Represents an iterable type.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class IterableType extends ScalarType implements TypeInterface
{
    use IterableTypeParseLiteralTrait;

    public function __construct()
    {
        $this->name = 'Iterable';
        $this->description = 'The `Iterable` scalar type represents an array or a Traversable with any kind of data.';

        parent::__construct();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        if (!is_iterable($value)) {
            throw new Error(sprintf('`Iterable` cannot represent non iterable value: %s', Utils::printSafe($value)));
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        if (!is_iterable($value)) {
            throw new Error(sprintf('`Iterable` cannot represent non iterable value: %s', Utils::printSafeJson($value)));
        }

        return $value;
    }

    /**
     * @param StringValueNode|BooleanValueNode|IntValueNode|FloatValueNode|ObjectValueNode|ListValueNode|ValueNode $valueNode
     */
    private function parseIterableLiteral($valueNode)
    {
        switch ($valueNode) {
            case $valueNode instanceof StringValueNode:
            case $valueNode instanceof BooleanValueNode:
                return $valueNode->value;
            case $valueNode instanceof IntValueNode:
                return (int) $valueNode->value;
            case $valueNode instanceof FloatValueNode:
                return (float) $valueNode->value;
            case $valueNode instanceof ObjectValueNode:
                $value = [];
                foreach ($valueNode->fields as $field) {
                    $value[$field->name->value] = $this->parseIterableLiteral($field->value);
                }

                return $value;
            case $valueNode instanceof ListValueNode:
                $list = [];
                foreach ($valueNode->values as $value) {
                    $list[] = $this->parseIterableLiteral($value);
                }

                return $list;
            default:
                return null;
        }
    }
}
