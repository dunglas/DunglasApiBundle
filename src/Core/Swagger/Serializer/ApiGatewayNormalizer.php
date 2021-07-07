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

namespace ApiPlatform\Core\Swagger\Serializer;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Removes features unsupported by Amazon API Gateway.
 *
 * @see https://docs.aws.amazon.com/apigateway/latest/developerguide/api-gateway-known-issues.html
 *
 * @internal
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ApiGatewayNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const API_GATEWAY = 'api_gateway';

    private $documentationNormalizer;
    private $defaultContext = [
        self::API_GATEWAY => false,
    ];

    public function __construct(NormalizerInterface $documentationNormalizer, $defaultContext = [])
    {
        $this->documentationNormalizer = $documentationNormalizer;
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->documentationNormalizer->normalize($object, $format, $context);
        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }

        if (!($context[self::API_GATEWAY] ?? $this->defaultContext[self::API_GATEWAY])) {
            return $data;
        }

        if (empty($data['basePath'])) {
            $data['basePath'] = '/';
        }

        foreach ($data['paths'] as $path => $operations) {
            foreach ($operations as $operation => $options) {
                if (isset($options['parameters'])) {
                    foreach ($options['parameters'] as $key => $parameter) {
                        if (!preg_match('/^[a-zA-Z0-9._$-]+$/', $parameter['name'])) {
                            unset($data['paths'][$path][$operation]['parameters'][$key]);
                        }
                        if (isset($parameter['schema']['$ref']) && $this->isLocalRef($parameter['schema']['$ref'])) {
                            $data['paths'][$path][$operation]['parameters'][$key]['schema']['$ref'] = $this->normalizeRef($parameter['schema']['$ref']);
                        }
                    }
                    $data['paths'][$path][$operation]['parameters'] = array_values($data['paths'][$path][$operation]['parameters']);
                }
                if (isset($options['responses'])) {
                    foreach ($options['responses'] as $statusCode => $response) {
                        if (isset($response['schema']['items']['$ref']) && $this->isLocalRef($response['schema']['items']['$ref'])) {
                            $data['paths'][$path][$operation]['responses'][$statusCode]['schema']['items']['$ref'] = $this->normalizeRef($response['schema']['items']['$ref']);
                        }
                        if (isset($response['schema']['$ref']) && $this->isLocalRef($response['schema']['$ref'])) {
                            $data['paths'][$path][$operation]['responses'][$statusCode]['schema']['$ref'] = $this->normalizeRef($response['schema']['$ref']);
                        }
                    }
                }
            }
        }

        foreach ($data['definitions'] as $definition => $options) {
            if (!isset($options['properties'])) {
                continue;
            }
            foreach ($options['properties'] as $property => $propertyOptions) {
                if (isset($propertyOptions['readOnly'])) {
                    unset($data['definitions'][$definition]['properties'][$property]['readOnly']);
                }
                if (isset($propertyOptions['$ref']) && $this->isLocalRef($propertyOptions['$ref'])) {
                    $data['definitions'][$definition]['properties'][$property]['$ref'] = $this->normalizeRef($propertyOptions['$ref']);
                }
                if (isset($propertyOptions['items']['$ref']) && $this->isLocalRef($propertyOptions['items']['$ref'])) {
                    $data['definitions'][$definition]['properties'][$property]['items']['$ref'] = $this->normalizeRef($propertyOptions['items']['$ref']);
                }
            }
        }

        // $data['definitions'] is an instance of \ArrayObject
        foreach (array_keys($data['definitions']->getArrayCopy()) as $definition) {
            if (!preg_match('/^[0-9A-Za-z]+$/', (string) $definition)) {
                $data['definitions'][preg_replace('/[^0-9A-Za-z]/', '', (string) $definition)] = $data['definitions'][$definition];
                unset($data['definitions'][$definition]);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->documentationNormalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return $this->documentationNormalizer instanceof CacheableSupportsMethodInterface && $this->documentationNormalizer->hasCacheableSupportsMethod();
    }

    private function isLocalRef(string $ref): bool
    {
        return '#/' === substr($ref, 0, 2);
    }

    private function normalizeRef(string $ref): string
    {
        $refParts = explode('/', $ref);

        $schemaName = array_pop($refParts);
        $schemaName = preg_replace('/[^0-9A-Za-z]/', '', $schemaName);
        $refParts[] = $schemaName;

        return implode('/', $refParts);
    }
}
