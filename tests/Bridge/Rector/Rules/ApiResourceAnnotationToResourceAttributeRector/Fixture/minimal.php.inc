<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource
 */
class Minimal
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Core\Annotation\ApiResource;

#[Resource]
class Minimal
{
}

?>
