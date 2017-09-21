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

namespace ApiPlatform\Core\Action;

use ApiPlatform\Core\Bridge\Graphql\ExecutorInterface;
use ApiPlatform\Core\Bridge\Graphql\Type\SchemaBuilderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * GraphQL API entrypoint.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class GraphqlEntrypointAction
{
    private $schemaBuilder;
    private $executor;
    private $twig;
    private $debug;
    private $title;
    private $graphiqlEnabled;

    public function __construct(SchemaBuilderInterface $schemaBuilder, ExecutorInterface $executor, \Twig_Environment $twig, bool $debug = false, bool $graphiqlEnabled = false, string $title = '')
    {
        $this->schemaBuilder = $schemaBuilder;
        $this->executor = $executor;
        $this->twig = $twig;
        $this->debug = $debug;
        $this->graphiqlEnabled = $graphiqlEnabled;
        $this->title = $title;
    }

    /**
     * @throws BadRequestHttpException
     */
    public function __invoke(Request $request): Response
    {
        if ($this->graphiqlEnabled && $request->isMethod('GET') && 'html' === $request->getRequestFormat()) {
            return new Response($this->twig->render('@ApiPlatform/Graphiql/index.html.twig', ['debug' => $this->debug, 'title' => $this->title]));
        }

        list($query, $operation, $variables) = $this->parseRequest($request);

        if (null === $query) {
            return new JsonResponse(['error' => 'GraphQL query is not valid'], Response::HTTP_BAD_REQUEST);
        }

        if (null === $variables) {
            return new JsonResponse(['error' => 'GraphQL variables are not valid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $executionResult = $this->executor->executeQuery($this->schemaBuilder->getSchema(), $query, null, null, $variables, $operation);

        return new JsonResponse($executionResult->toArray($this->debug));
    }

    private function parseRequest(Request $request): array
    {
        $query = $request->query->get('query');
        $operation = $request->query->get('operation');
        if ($variables = $request->query->get('variables', [])) {
            $variables = \json_decode($variables, true);
        }

        if (!$request->isMethod('POST')) {
            return [$query, $operation, $variables];
        }

        if ('json' === $request->getContentType()) {
            $input = \json_decode($request->getContent(), true);

            if (isset($input['query'])) {
                $query = $input['query'];
            }

            if (isset($input['variables'])) {
                $variables = \is_array($input['variables']) ? $input['variables'] : \json_decode($input['variables'], true);
            }

            if (isset($input['operation'])) {
                $operation = $input['operation'];
            }

            return [$query, $operation, $variables];
        }

        if ('application/graphql' === $request->headers->get('CONTENT_TYPE')) {
            $query = $request->getContent();
        }

        return [$query, $operation, $variables];
    }
}
