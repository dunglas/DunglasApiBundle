<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Metadata\Resource;
use PhpParser\Node;
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
        $reflectionClass = new \ReflectionClass($node->name->getAttribute('className'));

        foreach ($reflectionClass->getAttributes() as $attribute) {
            if (ApiResource::class !== $attribute->getName()) {
                continue;
            }

            $arguments = $this->resolveOperations($attribute->getArguments(), $node);
            $resourceAttributeGroup = $this->phpAttributeGroupFactory->createFromClassWithItems(Resource::class, $arguments);
            array_unshift($node->attrGroups, $resourceAttributeGroup);
        }

        $this->cleanupAttrGroups($node);

        return $node;
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

    /**
     * @param Class_ $node
     */
    private function resolveOperations(array $values, Node $node): array
    {
        foreach ($this->operationTypes as $type) {
            if (isset($values[$type])) {
                $operations = $this->formatOperations($values[$type]);
                foreach ($operations as $name => $arguments) {
                    $node->attrGroups[] = $this->createOperationAttributeGroup($type, $name, $arguments);
                }
                unset($values[$type]);
            }
        }

        return $values;
    }
}
