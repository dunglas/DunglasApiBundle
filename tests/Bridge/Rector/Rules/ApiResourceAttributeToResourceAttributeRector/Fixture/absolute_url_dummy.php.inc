<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;

#[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::ABS_URL)]
class AbsoluteUrlDummy
{
}

?>
-----
<?php

namespace ApiPlatform\Core\Tests\Bridge\Rector\Rules\ApiResourceAttributeToResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Resource;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;

#[Resource(urlGenerationStrategy: UrlGeneratorInterface::ABS_URL)]
class AbsoluteUrlDummy
{
}

?>
