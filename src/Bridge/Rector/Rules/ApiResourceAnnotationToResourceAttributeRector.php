<?php

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use ApiPlatform\Core\Bridge\Rector\Resolver\OperationClassResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\PhpAttribute\Printer\PhpAttributeGroupFactory;
use Symfony\Component\String\UnicodeString;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Webmozart\Assert\Assert;

final class ApiResourceAnnotationToResourceAttributeRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string
     */
    public const ANNOTATION_TO_ATTRIBUTE = 'api_resource_annotation_to_resource_attribute';
    /**
     * @var string
     */
    public const REMOVE_TAG = 'remove_tag';
    /**
     * @var AnnotationToAttribute[]
     */
    private $annotationsToAttributes = [];
    /**
     * @var bool
     */
    private $removeTag;
    /**
     * @var PhpAttributeGroupFactory
     */
    private $phpAttributeGroupFactory;
    /**
     * @var PhpDocTagRemover
     */
    private $phpDocTagRemover;

    public function __construct(PhpAttributeGroupFactory $phpAttributeGroupFactory, PhpDocTagRemover $phpDocTagRemover)
    {
        $this->phpAttributeGroupFactory = $phpAttributeGroupFactory;
        $this->phpDocTagRemover = $phpDocTagRemover;
    }

    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Change annotation to attribute', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(collectionOperations={}, itemOperations={
 *     "get",
 *     "get_by_isbn"={"method"="GET", "path"="/books/by_isbn/{isbn}.{_format}", "requirements"={"isbn"=".+"}, "identifiers"="isbn"}
 * })
 */
class Book
CODE_SAMPLE
            , <<<'CODE_SAMPLE'
use ApiPlatform\Metadata\Resource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Core\Annotation\ApiResource;

#[Resource]
#[Get]
#[Get(operationName: 'get_by_isbn', path: '/books/by_isbn/{isbn}.{_format}', requirements: ['isbn' => '.+'], identifiers: 'isbn')]
class Book
CODE_SAMPLE
            , [
                self::ANNOTATION_TO_ATTRIBUTE => [new AnnotationToAttribute('ApiPlatform\\Core\\Annotation\\ApiResource', 'ApiPlatform\\Core\\Annotation\\ApiResource')],
                self::REMOVE_TAG => true,
            ])
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Class_::class, Property::class, ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }

    /**
     * @param Class_|Property|ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node) : ?Node
    {
        if (!$this->isAtLeastPhpVersion(PhpVersionFeature::ATTRIBUTES)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $tags = $phpDocInfo->getAllTags();
        $hasNewAttrGroups = $this->processApplyAttrGroups($tags, $phpDocInfo, $node);
        if ($hasNewAttrGroups) {
            return $node;
        }
        return null;
    }

    /**
     * @param array<string, AnnotationToAttribute[]> $configuration
     */
    public function configure(array $configuration) : void
    {
        $annotationsToAttributes = $configuration[self::ANNOTATION_TO_ATTRIBUTE] ?? [];
        Assert::allIsInstanceOf($annotationsToAttributes, AnnotationToAttribute::class);
        $this->annotationsToAttributes = $annotationsToAttributes;
        $this->removeTag = $configuration[self::REMOVE_TAG] ?? true;
    }

    /**
     * @param array<PhpDocTagNode> $tags
     * @param Class_|Property|ClassMethod|Function_|Closure|ArrowFunction $node
     */
    private function processApplyAttrGroups(array $tags, PhpDocInfo $phpDocInfo, Node $node) : bool
    {
        $hasNewAttrGroups = \false;
        foreach ($tags as $tag) {
            foreach ($this->annotationsToAttributes as $annotationToAttribute) {
                $annotationToAttributeTag = $annotationToAttribute->getTag();
                if ($phpDocInfo->hasByName($annotationToAttributeTag)) {
                    if (true === $this->removeTag) {
                        // 1. remove php-doc tag
                        $this->phpDocTagRemover->removeByName($phpDocInfo, $annotationToAttributeTag);
                    }
                    // 2. add attributes
                    $node->attrGroups[] = $this->phpAttributeGroupFactory->createFromSimpleTag($annotationToAttribute);
                    $hasNewAttrGroups = \true;
                    continue 2;
                }
                if ($this->shouldSkip($tag->value, $phpDocInfo, $annotationToAttributeTag)) {
                    continue;
                }

                if (true === $this->removeTag) {
                    // 1. remove php-doc tag
                    $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $tag->value);
                }
                // 2. add attributes
                /** @var DoctrineAnnotationTagValueNode $tagValue */
                $tagValue = clone $tag->value;
                $this->resolveOperations($tagValue, $node);
                $resourceAttributeGroup = $this->phpAttributeGroupFactory->create($tagValue, $annotationToAttribute);
                array_unshift($node->attrGroups, $resourceAttributeGroup);
                $hasNewAttrGroups = \true;
                continue 2;
            }
        }
        return $hasNewAttrGroups;
    }

    private function shouldSkip(PhpDocTagValueNode $phpDocTagValueNode, PhpDocInfo $phpDocInfo, string $annotationToAttributeTag) : bool
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClass($annotationToAttributeTag);
        if ($phpDocTagValueNode !== $doctrineAnnotationTagValueNode) {
            return \true;
        }
        return !$phpDocTagValueNode instanceof DoctrineAnnotationTagValueNode;
    }

    private function resolveOperations(DoctrineAnnotationTagValueNode $tagValue, $node)
    {
        $values = $tagValue->getValues();

        foreach (['collectionOperations', 'itemOperations'] as $operationType) {
            if (isset($values[$operationType])) {
                foreach ($values[$operationType]->getValuesWithExplicitSilentAndWithoutQuotes() as $operationName => $items) {
                    /**
                     * For this cases:
                     * itemOperations={
                     *     "get_by_isbn"={"method"="GET", "path"="/books/by_isbn/{isbn}.{_format}", "requirements"={"isbn"=".+"}, "identifiers"="isbn"}
                     * }
                     */
                    if (is_array($items)) {
                        $items = ['operationName' => $operationName] + $items;

                        foreach ($items as $key => $item) {
                            $camelizedKey = (string) (new UnicodeString($key))->camel();
                            if ($key === $camelizedKey) {
                                continue;
                            }
                            $items[$camelizedKey] = $items[$key];
                            unset($items[$key]);
                        }
                    }
                    /**
                     * For this cases:
                     * collectionOperations={"get", "post"},
                     * itemOperations={"get", "put", "delete"},
                     */
                    if (is_string($items)) {
                        $operationName = $items;
                        $items = [];
                    }

                    $operationClass = OperationClassResolver::resolve($operationName, $operationType, $items);
                    $attribute = $this->phpAttributeGroupFactory->createFromClassWithItems($operationClass, $items);

                    $node->attrGroups[] = $attribute;
                }
                // Remove collectionOperations/itemOperations from Tag values
                $tagValue->removeValue($operationType);
            }
        }
    }
}
