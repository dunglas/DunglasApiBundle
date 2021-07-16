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

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * @deprecated since 2.6, to be removed in 3.0
 */
final class InheritedPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $resourceNameCollectionFactory;
    private $decorated;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, PropertyMetadataFactoryInterface $decorated = null)
    {
        @trigger_error(sprintf('"%s" is deprecated since 2.6 and will be removed in 3.0.', __CLASS__), \E_USER_DEPRECATED);

        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        @trigger_error(sprintf('"%s" is deprecated since 2.6 and will be removed in 3.0.', __CLASS__), \E_USER_DEPRECATED);

        $propertyMetadata = $this->decorated ? $this->decorated->create($resourceClass, $property, $options) : new PropertyMetadata();

        foreach ($this->resourceNameCollectionFactory->create() as $knownResourceClass) {
            if ($resourceClass === $knownResourceClass) {
                continue;
            }

            if (is_subclass_of($knownResourceClass, $resourceClass)) {
                $propertyMetadata = $this->create($knownResourceClass, $property, $options);

                return $propertyMetadata->withChildInherited($knownResourceClass);
            }
        }

        return $propertyMetadata;
    }
}
