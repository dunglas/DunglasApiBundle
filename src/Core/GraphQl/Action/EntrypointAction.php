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

namespace ApiPlatform\Core\GraphQl\Action;

use ApiPlatform\Core\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\Core\GraphQl\ExecutorInterface;
use ApiPlatform\Core\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\Error\Debug;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * GraphQL API entrypoint.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EntrypointAction
{
    private $schemaBuilder;
    private $executor;
    private $graphiQlAction;
    private $graphQlPlaygroundAction;
    private $normalizer;
    private $errorHandler;
    private $debug;
    private $graphiqlEnabled;
    private $graphQlPlaygroundEnabled;
    private $defaultIde;

    public function __construct(SchemaBuilderInterface $schemaBuilder, ExecutorInterface $executor, GraphiQlAction $graphiQlAction, GraphQlPlaygroundAction $graphQlPlaygroundAction, NormalizerInterface $normalizer, ErrorHandlerInterface $errorHandler, bool $debug = false, bool $graphiqlEnabled = false, bool $graphQlPlaygroundEnabled = false, $defaultIde = false)
    {
        $this->schemaBuilder = $schemaBuilder;
        $this->executor = $executor;
        $this->graphiQlAction = $graphiQlAction;
        $this->graphQlPlaygroundAction = $graphQlPlaygroundAction;
        $this->normalizer = $normalizer;
        $this->errorHandler = $errorHandler;
        if (class_exists(Debug::class)) {
            $this->debug = $debug ? Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE : false;
        } else {
            $this->debug = $debug ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE : DebugFlag::NONE;
        }
        $this->graphiqlEnabled = $graphiqlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->defaultIde = $defaultIde;
    }

    public function __invoke(Request $request): Response
    {
        try {
            if ($request->isMethod('GET') && 'html' === $request->getRequestFormat()) {
                if ('graphiql' === $this->defaultIde && $this->graphiqlEnabled) {
                    return ($this->graphiQlAction)($request);
                }

                if ('graphql-playground' === $this->defaultIde && $this->graphQlPlaygroundEnabled) {
                    return ($this->graphQlPlaygroundAction)($request);
                }
            }

            [$query, $operationName, $variables] = $this->parseRequest($request);
            if (null === $query) {
                throw new BadRequestHttpException('GraphQL query is not valid.');
            }

            $executionResult = $this->executor
                ->executeQuery($this->schemaBuilder->getSchema(), $query, null, null, $variables, $operationName)
                ->setErrorsHandler($this->errorHandler)
                ->setErrorFormatter([$this->normalizer, 'normalize']);
        } catch (\Exception $exception) {
            $executionResult = (new ExecutionResult(null, [new Error($exception->getMessage(), null, null, [], null, $exception)]))
                ->setErrorsHandler($this->errorHandler)
                ->setErrorFormatter([$this->normalizer, 'normalize']);
        }

        return new JsonResponse($executionResult->toArray($this->debug));
    }

    /**
     * @throws BadRequestHttpException
     */
    private function parseRequest(Request $request): array
    {
        $queryParameters = $request->query->all();
        $query = $queryParameters['query'] ?? null;
        $operationName = $queryParameters['operationName'] ?? null;
        if ($variables = $queryParameters['variables'] ?? []) {
            $variables = $this->decodeVariables($variables);
        }

        if (!$request->isMethod('POST')) {
            return [$query, $operationName, $variables];
        }

        if ('json' === $request->getContentType()) {
            return $this->parseData($query, $operationName, $variables, $request->getContent());
        }

        if ('graphql' === $request->getContentType()) {
            $query = $request->getContent();
        }

        if ('multipart' === $request->getContentType()) {
            return $this->parseMultipartRequest($query, $operationName, $variables, $request->request->all(), $request->files->all());
        }

        return [$query, $operationName, $variables];
    }

    /**
     * @throws BadRequestHttpException
     */
    private function parseData(?string $query, ?string $operationName, array $variables, string $jsonContent): array
    {
        if (!\is_array($data = json_decode($jsonContent, true))) {
            throw new BadRequestHttpException('GraphQL data is not valid JSON.');
        }

        if (isset($data['query'])) {
            $query = $data['query'];
        }

        if (isset($data['variables'])) {
            $variables = \is_array($data['variables']) ? $data['variables'] : $this->decodeVariables($data['variables']);
        }

        if (isset($data['operationName'])) {
            $operationName = $data['operationName'];
        }

        return [$query, $operationName, $variables];
    }

    /**
     * @throws BadRequestHttpException
     */
    private function parseMultipartRequest(?string $query, ?string $operationName, array $variables, array $bodyParameters, array $files): array
    {
        if ((null === $operations = $bodyParameters['operations'] ?? null) || (null === $map = $bodyParameters['map'] ?? null)) {
            throw new BadRequestHttpException('GraphQL multipart request does not respect the specification.');
        }

        [$query, $operationName, $variables] = $this->parseData($query, $operationName, $variables, $operations);

        /** @var string $map */
        if (!\is_array($decodedMap = json_decode($map, true))) {
            throw new BadRequestHttpException('GraphQL multipart request map is not valid JSON.');
        }

        $variables = $this->applyMapToVariables($decodedMap, $variables, $files);

        return [$query, $operationName, $variables];
    }

    /**
     * @throws BadRequestHttpException
     */
    private function applyMapToVariables(array $map, array $variables, array $files): array
    {
        foreach ($map as $key => $value) {
            if (null === $file = $files[$key] ?? null) {
                throw new BadRequestHttpException('GraphQL multipart request file has not been sent correctly.');
            }

            foreach ($map[$key] as $mapValue) {
                $path = explode('.', $mapValue);

                if ('variables' !== $path[0]) {
                    throw new BadRequestHttpException('GraphQL multipart request path in map is invalid.');
                }

                unset($path[0]);

                $mapPathExistsInVariables = array_reduce($path, static function (array $inVariables, string $pathElement) {
                    return \array_key_exists($pathElement, $inVariables) ? $inVariables[$pathElement] : false;
                }, $variables);

                if (false === $mapPathExistsInVariables) {
                    throw new BadRequestHttpException('GraphQL multipart request path in map does not match the variables.');
                }

                $variableFileValue = &$variables;
                foreach ($path as $pathValue) {
                    $variableFileValue = &$variableFileValue[$pathValue];
                }
                $variableFileValue = $file;
            }
        }

        return $variables;
    }

    /**
     * @throws BadRequestHttpException
     */
    private function decodeVariables(string $variables): array
    {
        if (!\is_array($decoded = json_decode($variables, true))) {
            throw new BadRequestHttpException('GraphQL variables are not valid JSON.');
        }

        return $decoded;
    }
}
