<?php

declare (strict_types=1);

namespace ApiPlatform\Core\Bridge\Rector\Set;

use Rector\Set\Contract\SetListInterface;

final class ApiPlatformSetList implements SetListInterface
{
    /**
     * @var string
     */
    public const ANNOTATION_TO_API_RESOURCE_ATTRIBUTE = __DIR__ . '/../config/sets/annotation-to-api-resource-attribute.php';
    /**
     * @var string
     */
    public const ANNOTATION_TO_RESOURCE_ATTRIBUTE = __DIR__ . '/../config/sets/annotation-to-resource-attribute.php';
    /**
     * @var string
     */
    public const ANNOTATION_TO_API_RESOURCE_AND_RESOURCE_ATTRIBUTE = __DIR__ . '/../config/sets/annotation-to-api-resource-and-resource-attribute.php';
    /**
     * @var string
     */
    public const ATTRIBUTE_TO_RESOURCE_ATTRIBUTE = __DIR__ . '/../config/sets/attribute-to-resource-attribute.php';
}
