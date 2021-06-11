<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\CreateItemAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\DoubleBatAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;

#[ApiResource(attributes: ['input' => false, 'output' => OutputDto::class], collectionOperations: ['post' => ['method' => 'POST', 'path' => '/dummy_dto_no_inputs', 'controller' => CreateItemAction::class], 'get'], itemOperations: ['get', 'delete', 'post_double_bat' => ['method' => 'POST', 'path' => '/dummy_dto_no_inputs/{id}/double_bat', 'controller' => DoubleBatAction::class, 'status' => 200]])]
class DummyDtoNoInput
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\CreateItemAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\DoubleBatAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;

#[Resource(attributes: ['input' => false, 'output' => OutputDto::class])]
#[Post(operationName: 'post', path: '/dummy_dto_no_inputs', controller: CreateItemAction::class)]
#[GetCollection]
#[Post(operationName: 'post_double_bat', path: '/dummy_dto_no_inputs/{id}/double_bat', controller: DoubleBatAction::class, status: 200)]
#[Get]
#[Delete]
class DummyDtoNoInput
{
}

?>
