<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use ApiPlatform\State\ProviderInterface;

class AttributeResourceProvider implements ProviderInterface
{
    public function provide(string $resourceClass, array $identifiers = [], array $context = [])
    {
        if (isset($identifiers['identifier'])) {
            return new AttributeResource($identifiers['identifier'], 'Foo');
        }

        return new AttributeResources([new AttributeResource(1, 'Foo'), new AttributeResource(2, 'Bar')]);
    }

    public function supports(string $resourceClass, array $identifiers = [], array $context = []): bool
    {
        return AttributeResource::class === $resourceClass || AttributeResources::class === $resourceClass;
    }
}
