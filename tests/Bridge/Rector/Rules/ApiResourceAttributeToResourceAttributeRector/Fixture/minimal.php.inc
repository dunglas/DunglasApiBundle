<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource]
class Minimal
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Core\Annotation\ApiResource;

#[Resource]
class Minimal
{
}

?>
