<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;

/**
 * @ApiResource(urlGenerationStrategy=UrlGeneratorInterface::ABS_URL)
 */
class AbsoluteUrlDummy
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;

#[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::ABS_URL)]
class AbsoluteUrlDummy
{
}

?>
