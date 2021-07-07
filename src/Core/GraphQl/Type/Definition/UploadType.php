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
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use Symfony\Component\HttpFoundation\File\UploadedFile;

if (\PHP_VERSION_ID >= 70200) {
    trait UploadTypeParseLiteralTrait
    {
        /**
         * {@inheritdoc}
         */
        public function parseLiteral(/*Node */ $valueNode, array $variables = null)
        {
            throw new Error('`Upload` cannot be hardcoded in query, be sure to conform to GraphQL multipart request specification.', $valueNode);
        }
    }
} else {
    trait UploadTypeParseLiteralTrait
    {
        /**
         * {@inheritdoc}
         */
        public function parseLiteral(Node $valueNode, array $variables = null)
        {
            throw new Error('`Upload` cannot be hardcoded in query, be sure to conform to GraphQL multipart request specification.', $valueNode);
        }
    }
}

/**
 * Represents an upload type.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
final class UploadType extends ScalarType implements TypeInterface
{
    use UploadTypeParseLiteralTrait;

    public function __construct()
    {
        $this->name = 'Upload';
        $this->description = 'The `Upload` type represents a file to be uploaded in the same HTTP request as specified by [graphql-multipart-request-spec](https://github.com/jaydenseric/graphql-multipart-request-spec).';

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
        throw new Error('`Upload` cannot be serialized.');
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value): UploadedFile
    {
        if (!$value instanceof UploadedFile) {
            throw new Error(sprintf('Could not get uploaded file, be sure to conform to GraphQL multipart request specification. Instead got: %s', Utils::printSafe($value)));
        }

        return $value;
    }
}
