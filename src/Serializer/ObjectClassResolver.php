<?php

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Util\ClassInfoTrait;

class ObjectClassResolver
{
    use ClassInfoTrait;

    public function __invoke($object)
    {
        return $this->getObjectClass($object);
    }
}
