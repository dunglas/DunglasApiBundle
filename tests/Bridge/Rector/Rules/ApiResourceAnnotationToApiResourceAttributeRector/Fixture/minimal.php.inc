<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

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

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
class Minimal
{
}

?>
