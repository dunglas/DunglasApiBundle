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

namespace ApiPlatform\Core\PathResolver;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\OperationTypeDeprecationHelper;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;

/**
 * Generates an operation path.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class OperationPathResolver implements OperationPathResolverInterface
{
    private $pathSegmentNameGenerator;

    public function __construct(PathSegmentNameGeneratorInterface $pathSegmentNameGenerator)
    {
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType/*, string $operationName = null*/): string
    {
        if (\func_num_args() < 4) {
            @trigger_error(sprintf('Method %s() will have a 4th `string $operationName` argument in version 3.0. Not defining it is deprecated since 2.1.', __METHOD__), \E_USER_DEPRECATED);
        }

        $operationType = OperationTypeDeprecationHelper::getOperationType($operationType);

        if (OperationType::SUBRESOURCE === $operationType) {
            throw new InvalidArgumentException('Subresource operations are not supported by the OperationPathResolver.');
        }

        $path = '/'.$this->pathSegmentNameGenerator->getSegmentName($resourceShortName);

        if (OperationType::ITEM === $operationType) {
            if (isset($operation['identifiers']) && (\count($operation['identifiers']) <= 1 || false === ($operation['has_composite_identifier'] ?? true))) {
                foreach ($operation['identifiers'] as $parameterName => $identifier) {
                    $path .= sprintf('/{%s}', \is_string($parameterName) ? $parameterName : $identifier);
                }
            } else {
                $path .= '/{id}';
            }
        }

        $path .= '.{_format}';

        return $path;
    }
}
