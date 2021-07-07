<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get"={
 *             "controller"=NotFoundAction::class,
 *             "read"=false,
 *             "output"=false
 *         }
 *     }
 * )
 */
class DisableItemOperation
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Core\Action\NotFoundAction;

#[ApiResource]
#[GetCollection]
#[Get(operationName: 'get', controller: NotFoundAction::class, read: false, output: false)]
class DisableItemOperation
{
}

?>
