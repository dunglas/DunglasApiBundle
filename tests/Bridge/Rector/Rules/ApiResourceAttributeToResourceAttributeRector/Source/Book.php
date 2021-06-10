<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Source;

use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(collectionOperations: [], itemOperations: ['get', 'get_by_isbn' => ['method' => 'GET', 'path' => '/books/by_isbn/{isbn}.{_format}', 'requirements' => ['isbn' => '.+'], 'identifiers' => 'isbn']])]
class Book
{

}
