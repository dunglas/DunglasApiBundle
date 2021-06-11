<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(itemOperations: ['get', 'get_custom' => ['method' => 'GET', 'path' => 'custom_action_collection_dummies/{id}'], 'custom_normalization' => ['route_name' => 'custom_normalization', 'method' => 'GET'], 'short_custom_normalization' => ['route_name' => 'short_custom_normalization', 'method' => 'GET']], collectionOperations: ['get', 'get_custom' => ['method' => 'GET', 'path' => 'custom_action_collection_dummies'], 'custom_denormalization' => ['route_name' => 'custom_denormalization', 'method' => 'GET'], 'short_custom_denormalization' => ['route_name' => 'short_custom_denormalization', 'method' => 'GET']])]
class CustomActionDummy
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Core\Annotation\ApiResource;

#[Resource]
#[GetCollection(operationName: 'get_custom', path: 'custom_action_collection_dummies')]
#[GetCollection(operationName: 'custom_denormalization', routeName: 'custom_denormalization')]
#[GetCollection(operationName: 'short_custom_denormalization', routeName: 'short_custom_denormalization')]
#[GetCollection]
#[Get(operationName: 'get_custom', path: 'custom_action_collection_dummies/{id}')]
#[Get(operationName: 'custom_normalization', routeName: 'custom_normalization')]
#[Get(operationName: 'short_custom_normalization', routeName: 'short_custom_normalization')]
#[Get]
class CustomActionDummy
{
}

?>
