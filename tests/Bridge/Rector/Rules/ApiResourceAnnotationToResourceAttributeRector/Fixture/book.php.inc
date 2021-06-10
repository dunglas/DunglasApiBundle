<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(collectionOperations={}, itemOperations={
 *     "get",
 *     "get_by_isbn"={"method"="GET", "path"="/books/by_isbn/{isbn}.{_format}", "requirements"={"isbn"=".+"}, "identifiers"="isbn"}
 * })
 */
class Book
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Core\Annotation\ApiResource;

#[Resource]
#[Get(operationName: 'get_by_isbn', path: '/books/by_isbn/{isbn}.{_format}', requirements: ['isbn' => '.+'], identifiers: 'isbn')]
#[Get]
class Book
{
}

?>
