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

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use ApiPlatform\Core\Bridge\Rector\Resolver\OperationClassResolver;
use PhpParser\Node\AttributeGroup;
use Rector\Core\Rector\AbstractRector;
use Rector\PhpAttribute\Printer\PhpAttributeGroupFactory;
use Symfony\Component\String\UnicodeString;

abstract class AbstractApiResourceToResourceAttribute extends AbstractRector
{
    protected PhpAttributeGroupFactory $phpAttributeGroupFactory;

    protected array $operationTypes = ['collectionOperations', 'itemOperations'];

    protected function normalizeOperations(array $operations): array
    {
        foreach ($operations as $name => $arguments) {
            /*
             * Case of custom action, ex:
             * itemOperations={
             *     "get_by_isbn"={"method"="GET", "path"="/books/by_isbn/{isbn}.{_format}", "requirements"={"isbn"=".+"}, "identifiers"="isbn"}
             * }
             */
            if (\is_array($arguments)) {
                // add operation name
                $arguments = ['operationName' => $name] + $arguments;
                foreach ($arguments as $key => $argument) {
                    // camelize argument name
                    $camelizedKey = (string) (new UnicodeString($key))->camel();
                    if ($key === $camelizedKey) {
                        continue;
                    }
                    $arguments[$camelizedKey] = $argument;
                    unset($arguments[$key]);
                }
            }

            /*
             * Case of default action, ex:
             * collectionOperations={"get", "post"},
             * itemOperations={"get", "put", "delete"},
             */
            if (\is_string($arguments)) {
                unset($operations[$name]);
                $name = $arguments;
                $arguments = [];
            }

            $operations[$name] = $arguments;
        }

        return $operations;
    }

    protected function createOperationAttributeGroup(string $type, string $name, array $arguments): AttributeGroup
    {
        $operationClass = OperationClassResolver::resolve($name, $type, $arguments);

        // remove unnecessary argument "method" after resolving the operation class
        if (isset($arguments['method'])) {
            unset($arguments['method']);
        }

        return $this->phpAttributeGroupFactory->createFromClassWithItems($operationClass, $arguments);
    }
}
