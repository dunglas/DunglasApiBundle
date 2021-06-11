<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Metadata\Resource;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpAttribute\Printer\PhpAttributeGroupFactory;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ApiResourceAttributeToResourceAttributeRector extends AbstractApiResourceToResourceAttribute implements ConfigurableRectorInterface
{
    /**
     * @var string
     */
    public const REMOVE_INITIAL_ATTRIBUTE = 'remove_initial_attribute';

    private bool $removeInitialAttribute;

    public function __construct(PhpAttributeGroupFactory $phpAttributeGroupFactory)
    {
        $this->phpAttributeGroupFactory = $phpAttributeGroupFactory;
    }

    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Upgrade ApiResource attribute to Resource and Operations attributes', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(collectionOperations: [], itemOperations: ['get', 'get_by_isbn' => ['method' => 'GET', 'path' => '/books/by_isbn/{isbn}.{_format}', 'requirements' => ['isbn' => '.+'], 'identifiers' => 'isbn']])]
class Book
CODE_SAMPLE
            , <<<'CODE_SAMPLE'
use ApiPlatform\Metadata\Resource;
use ApiPlatform\Metadata\Get;

#[Resource]
#[Get]
#[Get(operationName: 'get_by_isbn', path: '/books/by_isbn/{isbn}.{_format}', requirements: ['isbn' => '.+'], identifiers: 'isbn')]
class Book
CODE_SAMPLE
            , [self::REMOVE_INITIAL_ATTRIBUTE => true])]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Class_::class];
    }

    /**
     * @param array<string> $configuration
     */
    public function configure(array $configuration) : void
    {
        $this->removeInitialAttribute = $configuration[self::REMOVE_INITIAL_ATTRIBUTE] ?? true;
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node) : ?Node
    {
        foreach ($node->attrGroups as $key => $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (!$this->isName($attribute->name, ApiResource::class)) {
                    continue;
                }
                $items = $this->createItemsFromArgs($attribute->args);
                $arguments = $this->resolveOperations($items, $node);
                $resourceAttributeGroup = $this->phpAttributeGroupFactory->createFromClassWithItems(Resource::class, $arguments);
                array_unshift($node->attrGroups, $resourceAttributeGroup);
            }
        }

        $this->cleanupAttrGroups($node);

        return $node;
    }

    private function createItemsFromArgs(array $args) : array
    {
        $items = [];

        foreach ($args as $arg) {
            $itemValue = $this->normalizeNodeValue($arg->value);
            $itemName = $this->normalizeNodeValue($arg->name);
            $items[$itemName] = $itemValue;
        }

        return $items;
    }

    /**
     * @param mixed $value
     * @return bool|float|int|string|array<mixed>|Node\Expr
     */
    private function normalizeNodeValue($value)
    {
        if ($value instanceof ClassConstFetch) {
            return sprintf('%s::%s', (string) $value->class, (string) $value->name);
        }
        if ($value instanceof Array_) {
            return $this->normalizeNodeValue($value->items);
        }
        if ($value instanceof String_) {
            return (string) $value->value;
        }
        if ($value instanceof Identifier) {
            return $value->name;
        }
        if (\is_array($value)) {
            $items = [];
            foreach ($value as $itemKey => $itemValue) {
                if (null === $itemValue->key) {
                    $items[] = $this->normalizeNodeValue($itemValue->value);
                } else {
                    $items[$this->normalizeNodeValue($itemValue->key)] = $this->normalizeNodeValue($itemValue->value);
                }
            }

            return $items;
        }

        return $value;
    }

    /**
     * @param Class_ $node
     */
    private function resolveOperations(array $values, Node $node): array
    {
        foreach ($this->operationTypes as $type) {
            if (isset($values[$type])) {
                $operations = $this->normalizeOperations($values[$type]);
                foreach ($operations as $name => $arguments) {
                    $node->attrGroups[] = $this->createOperationAttributeGroup($type, $name, $arguments);
                }
                unset($values[$type]);
            }
        }

        return $values;
    }

    /**
     * Remove initial ApiResource attribute from node
     *
     * @param Class_ $node
     */
    private function cleanupAttrGroups(Node $node) : void
    {
        if (false === $this->removeInitialAttribute) {
            return;
        }

        foreach ($node->attrGroups as $key => $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($this->isName($attribute->name, ApiResource::class)) {
                    unset($node->attrGroups[$key]);
                    continue(2);
                }
            }
        }
    }
}
