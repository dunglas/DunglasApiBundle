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

namespace ApiPlatform\Util;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
trait OperationRequestInitiatorTrait
{
    /**
     * @var ResourceMetadataCollectionFactoryInterface
     */
    private $resourceMetadataCollectionFactory;

    /**
     * TODO: Kernel terminate remove the _api_operation attribute?
     */
    private function initializeOperation(Request $request): ?Operation
    {
        // TODO: 3.0 $resourceMetadataCollectionFactory is mandatory
        if (!$request->attributes->get('_api_resource_class') || !$this->resourceMetadataCollectionFactory) {
            return null;
        }

        if ($request->attributes->get('_api_operation')) {
            return $request->attributes->get('_api_operation');
        }

        $operation = $this->resourceMetadataCollectionFactory->create($request->attributes->get('_api_resource_class'))->getOperation($request->attributes->get('_api_operation_name'));
        $request->attributes->set('_api_operation', $operation);

        return $operation;
    }
}
